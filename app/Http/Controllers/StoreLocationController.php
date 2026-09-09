<?php

namespace App\Http\Controllers;

use App\Models\StoreLocation;
use App\Models\User;
use App\Services\Shopify\StoreLocationSyncService;
use Illuminate\Http\Request;

class StoreLocationController extends Controller
{
    public function index(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if (!$shop) {
            return response()->json([
                'status' => false,
                'message' => 'Shop not found',
            ], 404);
        }

        $includeInactive = filter_var($request->query('include_inactive', false), FILTER_VALIDATE_BOOLEAN);

        $query = StoreLocation::where('user_id', $shop->id)->orderBy('name');
        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return response()->json([
            'status' => true,
            'data' => $this->transformCollection($query->get()),
        ]);
    }

    public function sync(Request $request, StoreLocationSyncService $syncService)
    {
        $shop = User::where('name', $request->shop)->first();
        if (!$shop) {
            return response()->json([
                'status' => false,
                'message' => 'Shop not found',
            ], 404);
        }

        try {
            $locations = $syncService->syncAndList($shop);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Unable to sync store locations from Shopify',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Store locations synced successfully',
            'data' => $this->transformCollection($locations),
        ]);
    }

    private function transformCollection($locations)
    {
        return $locations->map(function ($location) {
            return [
                'id' => $location->id,
                'shopify_location_id' => $location->shopify_location_id,
                'shopify_location_gid' => $location->shopify_location_gid,
                'name' => $location->name,
                'address1' => $location->address1,
                'address2' => $location->address2,
                'city' => $location->city,
                'province' => $location->province,
                'province_code' => $location->province_code,
                'country' => $location->country,
                'country_code' => $location->country_code,
                'zip' => $location->zip,
                'phone' => $location->phone,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'is_active' => (bool) $location->is_active,
                'label' => $location->dropdown_label,
                'updated_at' => $location->updated_at,
            ];
        })->values();
    }
}
