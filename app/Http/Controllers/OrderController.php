<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Http;
use App\Models\Order;
use App\Models\Carrier;
use App\Models\OtherCarrierService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use Carbon\Carbon;
use App\Traits\EasyPostTrait;
use App\Traits\GoShippoTrait;
use App\Traits\LocalPickupTrait;
use App\Models\LabelTemplate;
use Illuminate\Support\Facades\Validator;



class OrderController extends Controller
{
    use EasyPostTrait, GoShippoTrait, LocalPickupTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tableColumns = Schema::getColumnListing('orders');
        $shop = User::where('name', $request->shop)->first();
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found']);
        }
        $query = Order::where('user_id', $shop->id)->orderBy('id', 'DESC');
        $searchTerm = trim($request->search);
        if (!empty($searchTerm) && $searchTerm !== "undefined") {
            $query->where(function ($q) use ($searchTerm, $tableColumns) {
                foreach ($tableColumns as $column) {
                    if (!empty($column)) {
                        $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            });
        }
        if (!empty($request->page) && !empty($request->type)) {
            if ($request->type === 'Current') {
                $requestData = $query->whereDate('date', Carbon::today())->paginate(50);
            } else {
                $requestData = $query->where('fullfilement', $request->type)
                    ->paginate(50);
            }
            return response()->json(['status' => true, 'data' => $requestData]);
        }
        return response()->json(['status' => false, 'message' => 'Invalid request', 'data' => []]);
    }


    public function getShppingRates(Request $request)
    {
        $shop = $request->query('shop');
        $order_id = $request->query('order_id');
        $service_id = $request->query('service_id');
        $carrier_id = $request->query('carrier_id');
        $dimension_id = $request->query('dimension_id');

        // dd($request->all());
        if (empty($shop)) {
            return response()->json(["status" => false, "message" => "shop is required"]);
        }
        if (empty($order_id)) {
            return response()->json(["status" => false, "message" => "order_id is required"]);
        }
        if (empty($service_id)) {
            return response()->json(["status" => false, "message" => "service_id is required"]);
        }
        if (empty($carrier_id)) {
            return response()->json(["status" => false, "message" => "carrier_id is required"]);
        }
        if (empty($dimension_id)) {
            return response()->json(["status" => false, "message" => "Dimension is required"]);
        }
        $user = User::where('name', $shop)->first();

        if (!$user) {
            return response()->json(['status' => false, "message" => "Shop not found"]);
        }

        $order =  Order::where('user_id', $user->id)->where('id', $order_id)->first();

        if (!$order) {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }

        $shopifyOrder = $this->getShopifyOrder($order->order_id, $user);

        if (isset($shopifyOrder['order']['id'])) {
            $carrier_service = OtherCarrierService::where('user_id', $user->id)->where('id', $service_id)->first();

            if (!$carrier_service) {
                return response()->json(['status' => false, "message" => "Carrier service not found"]);
            }
            if ($carrier_service->carrier_type == "easypost") {
                return $this->createEasyPostCustomsInfo($carrier_service, $shopifyOrder['order'], $user, $carrier_id, $dimension_id);
            } elseif ($carrier_service->carrier_type == "shippo") {
                return $this->createGoShhippoShipmentRate($carrier_service, $shopifyOrder['order'], $user, $carrier_id, $dimension_id);
            } else {
                return response()->json(['status' => false, "message" => "Carrier service not found"]);
            }
        } else {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }
    }

    public function getShopifyOrder($order_id, $shop)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $shop->password,
        ])->get('https://' . $shop->name . '/admin/api/2024-04/orders/' . $order_id . '.json');

        return $response->json();
    }


    public function createShippingLabel(Request $request)
    {
        // Step 1: Validate Request Data
        $validator = Validator::make($request->all(), [
            'shipment_id'  => 'required',
            'rate_id'      => 'required',
            'service_id'   => 'required', // Ensure service_id exists in DB
            'carrier_id'   => 'required',
            'order_id'     => 'required',
            'rate_price'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Step 2: Fetch API Key from Database Based on service_id
        $service = OtherCarrierService::where('id', $request->service_id)->first();
        if (!$service || empty($service->api_key)) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found for the selected service.',
            ], 400);
        }

        if ($service->carrier_type == "easypost") {
            return $this->createEasyPostLabel($service, $request);
        } elseif ($service->carrier_type == "shippo") {
            return $this->createShippoLabel($service, $request);
        } else {
            return response()->json(['status' => false, "message" => "Carrier service not found"]);
        }
    }


    public function createLocalShippingLabel(Request $request)
    {
        // Step 1: Validate Request Data
        $validator = Validator::make($request->all(), [
            'template_id'  => 'required',
            'order_id'     => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('name', $request->shop)->first();

        if (!$user) {
            return response()->json(['status' => false, "message" => "Shop not found"]);
        }

        $order =  Order::where('user_id', $user->id)->where('id', $request->order_id)->first();

        if (!$order) {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }

        $shopifyOrder = $this->getShopifyOrder($order->order_id, $user);
        if (isset($shopifyOrder['order']['id'])) {
            return $this->localShippingLabel($shopifyOrder['order'], $user, $request);
        } else {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }
    }

    public function printLabel(Request $request, $id)
    {

        $user = User::where('name', $request->shop)->first();

        if (!$user) {
            return response()->json(['status' => false, "message" => "Shop not found"]);
        }

        $order =  Order::where('user_id', $user->id)->where('id', $id)->first();

        if (!$order) {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }

        if ($order->group_title == "Local Shipping") {
            return response()->json(['status' => true, "label_link" => $order->label_url]);
        } else {

            $imageUrl = $order->label_url; // PNG URL from the order

            $service = OtherCarrierService::where('id', $order->shipping_service)->first();

            if ($service->carrier_type == "shippo") {
                return response()->json(['status' => true, "label_link" => $order->label_url]);
            }
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                return response()->json(['status' => false, "message" => "Invalid label URL"]);
            }

            // Enable remote images for DOMPDF
            PDF::setOptions(['isRemoteEnabled' => true]);
            $imageData = @file_get_contents($imageUrl);
            if ($imageData === false) {
                return response()->json(['status' => false, "message" => "Failed to fetch image"]);
            }
            $base64Image = 'data:image/png;base64,' . base64_encode($imageData);



            // Generate PDF
            // $pdf = PDF::loadHTML($html);
            // // return response()->json(['status'=>true,"label_link"=>$pdf->stream('shipping_label.pdf')]);
            // // return $pdf->download('download.pdf');
            // // **Return PDF directly as a response (no storage)**
            // return $pdf->stream('shipping_label.pdf');
            $pdf = PDF::loadView('pdf.return-label', ['imagePath' => $base64Image]);

            // // Output the PDF to a file
            // $pdf->save($pdfPath);
            return $pdf->stream('shipping_label.pdf');
            // Return a response to download the PDF
            return $pdf->download('download.pdf');
        }
    }


    public function orderFulfilled(Request $request, $id)
    {
        $client = new \GuzzleHttp\Client();
        $shop = User::where('name', $request->shop)->first();

        if (!$shop) {
            return response()->json(['status' => false, "message" => "Shop not found"]);
        }

        $order = Order::where('user_id', $shop->id)
            ->where('id', $id)
            ->where('fullfilement', '!=', 'Fulfilled')
            ->first();

        if (!$order) {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }
        if ($shop) {
            $headers = [
                'X-Shopify-Access-Token: ' . $shop->password,
                'Content-Type: application/json',
            ];

            $status = 0;

            if (!empty($order->label_url)) {
                $fulfillmentOrder = $this->getOrderDetails($order->order_id, $shop);

                if (isset($fulfillmentOrder->fulfillment_orders) && !empty($fulfillmentOrder->fulfillment_orders)) {
                    $orderItems = [];

                    foreach ($fulfillmentOrder->fulfillment_orders[0]->line_items as $item) {
                        $orderItems[] = [
                            'id' => $item->id,
                            'quantity' => $item->quantity,
                        ];
                    }

                    $jsonBody = json_encode([
                        'fulfillment' => [
                            'line_items_by_fulfillment_order' => [
                                [
                                    'fulfillment_order_id' => $fulfillmentOrder->fulfillment_orders[0]->id,
                                    'fulfillment_order_line_items' => $orderItems
                                ]
                            ]
                        ],

                    ]);

                    $url = 'https://' . $shop->name . '/admin/api/' . env("SHOPIFY_API_VERSION") . '/fulfillments.json';

                    sleep(1);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $data = json_decode($response, true);

                    if ($data) {

                        $orders = Order::where('id', $order->id)->update(['fullfilement' => $data['fulfillment']['line_items'][0]['fulfillment_status']]);

                        if ($orders) {
                            $status = 1;
                        } else {
                            $status = 0;
                        }
                    } else {
                        return response()->json(['status' => false, 'message' => 'Failed to fulfill order']);
                    }
                }
            } else {
                $status = 2;
            }


            if ($status === 1) {
                return response()->json(['status' => true, 'message' => 'Order Fulfilled Successfully']);
            } else if ($status === 2) {
                return response()->json(['status' => false, 'message' => 'Purchase label is not found']);
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to fulfill order']);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Ecom account connection not established']);
        }
    }
    function getOrderDetails($order_id, $shop)
    {
        $client = new \GuzzleHttp\Client();
        $headers['X-Shopify-Access-Token'] = $shop->password;
        $headers['Content-Type'] = "application/json";
        $url  = "https://" . $shop->name . "/admin/api/" . env("SHOPIFY_API_VERSION") . "/orders/" . $order_id . "/fulfillment_orders.json";
        $response = $client->request('GET', $url, ['headers' => $headers]);
        $data = json_decode($response->getBody());
        return $data;
    }


    public function printOrderDetailsPackingSlip(Request $request)
    {
        // dd($request->all());
        $orderID = str_replace("gid://shopify/Order/", "", $request->orderId);
        $templateID = $request->templateId;
        $user = User::where('name', $request->shop)->first();

        if (!$user) {
            return response()->json(['status' => false, "message" => "Shop not found"]);
        }

        $order = Order::where('order_id', $orderID)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }

        $shopifyOrder = $this->getShopifyOrder($order->order_id, $user);
        $request->merge(['carrier_label' => $order->carrier_label]);
        if (isset($shopifyOrder['order']['id'])) {
            return $this->localShippingLabel($shopifyOrder['order'], $user, $request, "print");
        } else {
            return response()->json(['status' => false, "message" => "Order not found"]);
        }
    }

    public function getTemplatesForOrderDetailPage(Request $request)
    {
        $user = User::where('name', $request->shop)->first();
        if (!$user) {
            return response()->json(['status' => false, "message" => "Shop not found"]);
        }
        $templates = LabelTemplate::where('user_id', $user->id)->get();
        if ($templates) {
            return response()->json([
                "status" => true,
                "templates" => $templates
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No templates found"
            ]);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
