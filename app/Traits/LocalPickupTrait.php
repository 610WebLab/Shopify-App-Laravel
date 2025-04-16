<?php

namespace App\Traits;

use App\Models\Localpickup;
use App\Traits\FetchShippingZoneTrate;
use App\Models\Order;
use App\Models\LabelTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Milon\Barcode\DNS1D;
use Http;
use App\Traits\FreeShippingTrait;
use App\Traits\TableRateTrait;
use App\Traits\FlatRateTrait;
use App\Traits\DistanceRatesTrait;
use App\Models\Flaterate;
use App\Models\Freeshipping;
use App\Models\Tablerates;
use App\Models\RatesByDistance;
use DateTime;

trait LocalPickupTrait
{
    use FetchShippingZoneTrate, FreeShippingTrait, FlatRateTrait, TableRateTrait, DistanceRatesTrait;

    public function localPickUpShipping($country_code, $province_code, $post_code)
    {

        // if ($country_code && $province_code || $post_code) 
        // {

        // $state = $country_code.":".$province_code;

        // $zone =  Shippingzone::whereRaw('FIND_IN_SET(?, country)', [$country_code])->orWhereRaw('FIND_IN_SET(?, state)', [$state])->where('status', 1)->first();

        // if(empty($zone)){
        //     $zone = Shippingzone::where('country','')->where('state','')->where('status', 1)->first();

        // }
        $zone = json_decode($this->getShippingZones($country_code, $province_code, $post_code));
        if (!empty($zone)) {

            return $this->calCulateLocalPickUp($zone->id);
        } else {

            return response()->json([]);
        }

        // }
    }

    public function calCulateLocalPickUp($zoneID)
    {
        $result = [];
        $localPick = Localpickup::where('zone_id', $zoneID)->where('status', 1)->get();

        if ($localPick) {
            foreach ($localPick as $local) {
                if ($local->tax_status  == 'taxable') {
                    array_push($result, array('status' => 1, 'service_name' => $local->title, 'description' => '',  'shipPrice' => $local->pickup_cost));
                } else {
                    array_push($result, array('status' => 1, 'service_name' => $local->title, 'description' => '',  'shipPrice' => $local->pickup_cost));
                }
            }
            return json_encode($result);
        }
    }

    public function localShippingLabel($shopifyOrder, $user, $request, $adminPrint = null)
    {
        // 1) Retrieve shop info (Your custom method)
        // dd($request->all(), $user->toArray(), $shopifyOrder);
        $shopInfo = $this->getShopInfo3($user);
        if (!$shopInfo['shop']) {
            return response()->json([
                'status'  => false,
                'message' => 'Shop not found',
            ]);
        }

        // 2) Build "from_address" using the shop info
        $fromAddress = [
            'name'    => $shopInfo['shop']['name']           ?? '',
            'street1' => $shopInfo['shop']['address1']       ?? '',
            'city'    => $shopInfo['shop']['city']           ?? '',
            'state'   => $shopInfo['shop']['province_code']  ?? '',
            'zip'     => $shopInfo['shop']['zip']            ?? '',
            'country' => $shopInfo['shop']['country_code']   ?? '',
            'phone'   => $shopInfo['shop']['phone']          ?? '',
            'email'   => $shopInfo['shop']['email']          ?? '',
        ];

        // 3) Build "to_address" using Shopify order shipping info
        $toAddress = [
            'name'    => $shopifyOrder['shipping_address']['name']
                ?? $shopifyOrder['shipping_address']['company']
                ?? 'Customer Name',
            'street1' => $shopifyOrder['shipping_address']['address1']       ?? '',
            'city'    => $shopifyOrder['shipping_address']['city']           ?? '',
            'state'   => $shopifyOrder['shipping_address']['province_code']  ?? '',
            'zip'     => $shopifyOrder['shipping_address']['zip']            ?? '',
            'country' => $shopifyOrder['shipping_address']['country_code']   ?? '',
            'phone'   => $shopifyOrder['shipping_address']['phone']          ?? '',
            'email'   => $shopifyOrder['shipping_address']['email']          ?? '',
        ];


        $labelCount = $request->label_count;
        $freeShips = null;
        $flateRate = null;
        $localPickup = null;
        $tableRate = null;
        $distanceRate = null;
        $price = 0;
        $weight = 0;
        $quantity = 0;
        $lineItem = 0;
        $shipPrice = 0;
        $lineItem = count($shopifyOrder['line_items']);
        foreach ($shopifyOrder['line_items'] as $checkout) {
            $price = $price + ($checkout['price'] * $checkout['quantity']);
            $weight = $weight + ($checkout['grams'] * $checkout['quantity']);
            $quantity = $quantity + $checkout['quantity'];
        }

        $carrierLabel = $request->has('carrier_label') ? $request->carrier_label : null; // The selected carrier label


        // Check if carrier_label includes any available carrier name
        if (str_contains($carrierLabel, "Free Shipping")) {

            $freeShips = json_decode($this->minimumOrderAmount($price, $toAddress['country'], $toAddress['state'], $toAddress['zip']), true);
            if ($freeShips && isset($freeShips[0][0]['shipPrice'])) {
                $shipPrice = $freeShips[0][0]['shipPrice'];
            }
        } elseif (str_contains($carrierLabel, "Flat Rate")) {

            $flateRate =  json_decode($this->flatRateShipping($toAddress['country'], $toAddress['state'], $toAddress['zip']), true);
            if ($flateRate && isset($flateRate[0][0]['shipPrice'])) {
                $shipPrice = $flateRate[0][0]['shipPrice'];
            }
        } elseif (str_contains($carrierLabel, "Local Pickup")) {

            $localPickup =  json_decode($this->localPickUpShipping($toAddress['country'], $toAddress['state'], $toAddress['zip']), true);
            if ($localPickup && isset($localPickup[0][0]['shipPrice'])) {
                $shipPrice = $localPickup[0][0]['shipPrice'];
            }
        } elseif (str_contains($carrierLabel, "Table Rate")) {

            $tableRate = json_decode($this->tableRateShipping($toAddress['country'], $toAddress['state'], $toAddress['zip'], $price, $weight, $quantity, $lineItem), true);
            if ($tableRate && isset($tableRate[0][0]['shipPrice'])) {
                $shipPrice = $tableRate[0][0]['shipPrice'];
            }
        } elseif (str_contains($carrierLabel, "Distance Rate")) {

            $distanceRate = json_decode($this->DistanceRateShipping($toAddress['country'], $toAddress['state'], $toAddress['zip'], $toAddress['street1'], $price, $weight, $quantity, $lineItem), true);
            if ($distanceRate && isset($distanceRate[0][0]['shipPrice'])) {
                $shipPrice = $distanceRate[0][0]['shipPrice'];
            }
        } else {
            return response()->json(['error' => 'Invalid carrier label'], 400);
        }



        // 4) Retrieve the label template from DB
        $template = LabelTemplate::findOrFail($request->template_id);

        // // 5) Prepare additional data (tracking, weight, package info, etc.)
        // //    (Adjust or remove as needed for your design.)
        $trackingNumber   = $shopifyOrder['tracking_number'] ?? '1234-5678-ABCD';


        // // (Optional) Generate a QR code based on order_number, tracking, etc.
        $dns1d = new DNS1D;
        $barcodePng = $dns1d->getBarcodePNG($shopifyOrder['order_number'] ?? '12345', 'C128', 2, 70);
        $barcode = 'data:image/png;base64,' . $barcodePng;
        $order2 = Order::where('id', $request->order_id)->first();

        // // 6) Build the array of placeholders => actual values
        // //    (Adjust keys to match your template placeholders.)
        $placeholders = [];
        $templateContent = [];
        $total_price = 0;
        if (isset($shopifyOrder['line_items']) && is_array($shopifyOrder['line_items'])) {
            $labelCount = $request->label_count ?? 1;
            
            // Handle single item case
            if (count($shopifyOrder['line_items']) === 1) {
                $lineItemChunks = [collect($shopifyOrder['line_items'])];
            } else {
                $lineItemChunks = collect($shopifyOrder['line_items'])->chunk(ceil(count($shopifyOrder['line_items']) / $labelCount));
            }
            foreach ($lineItemChunks as $chunk) {
                $total_price = 0;
                $chunkArray = $chunk->toArray();
        
                foreach ($chunkArray as $item) {
                    $total_price += (float) $item['price'];
                }
        
                $placeholders = [
                    '{logo_url}'       => $template->logo_url ?? '',
                    '{order_number}'   => $order2->order_no ?? '',
                    '{order_date}'     => isset($order2->date) ? (new DateTime($order2->date))->format('d-m-Y') : '',
                    '{tracking_number}' => $trackingNumber,
                    '{from_name}'       => $fromAddress['name'],
                    '{from_street1}'    => $fromAddress['street1'],
                    '{from_city}'       => $fromAddress['city'],
                    '{from_state}'      => $fromAddress['state'],
                    '{from_zip}'        => $fromAddress['zip'],
                    '{from_country}'    => $fromAddress['country'],
                    '{from_phone}'      => $fromAddress['phone'],
                    '{from_email}'      => $fromAddress['email'],
                    '{to_name}'         => $toAddress['name'],
                    '{to_street1}'      => $toAddress['street1'],
                    '{to_city}'         => $toAddress['city'],
                    '{to_state}'        => $toAddress['state'],
                    '{to_zip}'          => $toAddress['zip'],
                    '{to_country}'      => $toAddress['country'],
                    '{to_phone}'        => $toAddress['phone'],
                    '{to_email}'        => $toAddress['email'],
                    '{bar_code}'        => $barcode ?? '',
                    '{total_price}'     => number_format($total_price + $shipPrice, 2),
                    '{items}'           => implode(', ', array_map(fn($item) => $item['name'] . ' (Qty: ' . $item['quantity'] . ')', $chunkArray)),
                    '{item_name}'       => $chunkArray[0]['name'] ?? 'N/A' // Handle single or multiple items safely
                ];
        
                $templateContent[] = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $template->content
                );
            }
        }
        



        $finalHtml = implode('<p style="page-break-before: always;"></p> ', $templateContent);
        // // dd( $finalHtml);

        if(!empty($adminPrint)) {
            return $finalHtml ;
        }
        $pdf = PDF::loadHTML($finalHtml);

        // // 9) (Optional) Save the PDF to public/labels
        $fileName = 'local_label_' . $request->template_id . '_' . time() . '.pdf';
        $path = 'local_labels/' . $fileName;
        Storage::disk('public')->makeDirectory('local_labels');

        // // Save the PDF content to the public disk using Storage
        Storage::disk('public')->put($path, $pdf->output());

        // // Get the public URL for the saved PDF
        $pdfUrl = Storage::disk('public')->url($path);

        // // 10) Update the order record with label URL
        $order = Order::find($request->order_id);
        if ($order) {
            $order->label_url =  $pdfUrl;
            $order->template_id = $request->template_id;
            $order->group_title = $request->group_title;
            $order->carrier_id = $request->carrier_id;
            $order->shipping_service = $request->service_id;
            $order->carrier_label = $request->carrier_label;
            $order->label_count = $request->label_count;
            $order->dimension_id = $request->dimension_id;
            $order->save();
        }

        // // 11) Return success response with PDF URL
        return response()->json([
            'status'  => true,
            'message' => 'Local shipping label created successfully.',
            'data'    => [
                'pdf_url' => url('labels/' . $fileName),
            ],
        ]);
    }


    public function getShopInfo3($shop)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $shop->password,
        ])->get('https://' . $shop->name . '/admin/api/2025-01/shop.json');

        return $response->json();
    }

    public function generatePdf($id)
    {
        $template = LabelTemplate::findOrFail($id);

        $data = [
            'order_name' => 'Order ABC',
            'order_number' => '12345',
            'item_number' => 'ITM-67890',
            'price' => '$99.99',
            'qr_code' => 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/qr_code.png')))
        ];

        $templateContent = str_replace(
            ['{order_name}', '{order_number}', '{item_number}', '{price}', '{qr_code}'],
            [$data['order_name'], $data['order_number'], $data['item_number'], $data['price'], $data['qr_code']],
            $template->content
        );
        $pdf = PDF::loadView('pdf.label_template', [
            'template' => $template,
            'templateContent' => $templateContent
        ]);
        $pdf = PDF::loadView('pdf.label_template', [
            'template' => $template,
            'templateContent' => $templateContent
        ]);

        return $pdf->stream('label_template_' . $template->id . '.pdf');
    }
}
