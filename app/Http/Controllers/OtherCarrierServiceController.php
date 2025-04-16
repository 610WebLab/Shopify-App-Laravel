<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OtherCarrierService;
use Illuminate\Support\Facades\Validator;  // Add this line
use App\Traits\EasyPostTrait;
use App\Models\Carrier;
use App\Traits\GoShippoTrait;

class OtherCarrierServiceController extends Controller
{
    use EasyPostTrait, GoShippoTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $shopName = $request->query('shop');

        if (!$shopName) {
            return response()->json([
                'success' => false,
                'error' => 'Shop parameter is required'
            ], 400);
        }
        $shop = User::where('name', $shopName)->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'error' => 'Shop not found'
            ], 404);
        }
        if(isset($request->page) && $request->page === "carrier") {
            $services = OtherCarrierService::with(['carrier' => function ($query) {
                $query->where('status', 'active'); // Apply condition on 'carrier' relationship
            }])
            ->where('user_id', $shop->id)
            ->where('status', 'active')
            ->get();
            
            return response()->json(['status' => true, 'data' => $services]);
        }else {
            return response()->json(OtherCarrierService::with('carrier')->where('user_id', $shop->id)->get());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $shopName = $request->query('shop');
            $service_type = $request->input('service_type');
            if (!$shopName) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shop parameter is required'
                ], 400);
            }
            $shop = User::where('name', $shopName)->first();

            if (!$shop) {
                return response()->json(['status' => false, 'message' => 'Shop not found']);
            }
            switch ($service_type) {
                case 'easypost':
                    return $this->processEasyPostCarrier($shop, $service_type, $request);
                case 'shippo':
                    return $this->processGoShippoCarrier($shop, $service_type, $request);
                default:
                    return response()->json(['status' => false, 'message'   => 'Service type not supported']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Something went wrong while creating the carrier.', 'message' => $e->getMessage()]);
        }
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
        try {
            $OtherCarrie = OtherCarrierService::find($id);
            return response()->json([
                'success' => true,
                'data' => $OtherCarrie
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while deleting the carrier.',
                'message' => $e->getMessage()
            ], 500);
        }
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
        try {
            $shopName = $request->query('shop');
            $service_type = $request->input('service_type');
            $shop = User::where('name', $shopName)->first();

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shop not found'
                ], 404);
            }
            switch ($service_type) {
                case 'easypost':
                    return $this->updateEasyPostCarrier($shop, $service_type, $request, $id);
                case 'shippo':
                    return $this->updateGoShippoCarrier($shop, $service_type, $request, $id);
                default:
                    return response()->json(['status' => false,'message'   => 'Service type not supported']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error'   => 'Something went wrong while updating the carrier service.', 'message' => $e->getMessage()]);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $OtherCarrie = OtherCarrierService::find($id);
            if (!$OtherCarrie) {
                return response()->json(['status' => false, 'message' => 'Carrier service not found']);
            }
            Carrier::where('service_id', $id)->delete();
            $OtherCarrie->delete();
            return response()->json(['status' => true, 'message' => "Record deleted successfully"]);
        } catch (\Exception $e) {
            return response()->json([ 'status' => false, 'error' => 'Something went wrong while deleting the carrier.', 'message' => $e->getMessage() ]);
        }
    }
    
    public function updateOtherCarrierStatus($id, Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if ($shop) {
            $getData = OtherCarrierService::where('id', $id)->where('user_id', $shop->id)->first();
            if ($getData) {
                $getData->status = $request->status;
                if ($getData->save()) {
                    return response()->json(['status' => true, 'message' => 'Carrier service status update successfully']);
                } else {
                    return response()->json(['status' => false, 'message' => 'Failed to update carrier service status']);
                }
            } else {
                return response()->json(['status' => false, 'message' => "Carrier service not found"]);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Shop not found']);
        }
    }
}
