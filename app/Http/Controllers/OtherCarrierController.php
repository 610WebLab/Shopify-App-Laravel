<?php

namespace App\Http\Controllers;

use App\Models\OtherCarrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;  // Add this line
use App\Models\User;

class OtherCarrierController extends Controller {
    // Get all carriers
    public function index() {
        return response()->json(OtherCarrier::all());
    }

    // Store a new carrier
    public function store(Request $request)
    {
        try {
            // Extract shop from query parameters
            $shopName = $request->query('shop');
    
            if (!$shopName) {
                return response()->json([
                    'success' => false,
                    'error' => 'Shop parameter is required'
                ], 400);
            }
    
            // Validate request body
            $validator = Validator::make($request->all(), [
                'carrier_name' => 'required|unique:other_carriers',
                'account_id' => 'required',
                'api_key' => 'required',
                'status' => 'required|in:active,inactive',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }
    
            // Find the shop
            $shop = User::where('name', $shopName)->first();
    
            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'error' => 'Shop not found'
                ], 404);
            }
    
            // Create the carrier
            $carrier = OtherCarrier::create([
                'shop_id' => $shop->id,
                'carrier_name' => $request->carrier_name,
                'account_id' => $request->account_id,
                'api_key' => $request->api_key,
                'status' => $request->status
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Carrier created successfully',
                'data' => $carrier
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while creating the carrier.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    

    // Show a specific carrier
    public function show($id) {
        return response()->json(OtherCarrier::findOrFail($id));
    }

    // Update carrier
    public function update(Request $request, $id) {
        $carrier = OtherCarrier::findOrFail($id);
        $carrier->update($request->all());

        return response()->json($carrier);
    }

    // Delete a carrier
    public function destroy($id) {
        OtherCarrier::findOrFail($id)->delete();
        return response()->json(['message' => 'Carrier deleted']);
    }

   
}

