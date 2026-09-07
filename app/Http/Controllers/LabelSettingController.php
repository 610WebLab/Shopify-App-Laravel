<?php

namespace App\Http\Controllers;

use App\Models\LabelSetting;
use App\Models\LabelTemplate;
use App\Models\User;
use App\Services\Shopify\ShopifyAdminClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LabelSettingController extends Controller
{
    public function show(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found'], 404);
        }

        $settings = LabelSetting::where('user_id', $shop->id)->first();

        if (!$settings) {
            $settings = $this->seedFromShopifyShop($shop);
        }

        return response()->json([
            'status' => true,
            'data' => $settings,
        ]);
    }

    public function addresses(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found'], 404);
        }

        $client = ShopifyAdminClient::for($shop);
        $storeResponse = $client->getJson('shop.json');
        $store = $storeResponse['shop'] ?? [];

        $addresses = [
            [
                'id' => 'store',
                'source' => 'store',
                'name' => $store['name'] ?? 'Store address',
                'label' => 'Store address' . (!empty($store['name']) ? ' (' . $store['name'] . ')' : ''),
                'company_name' => $store['name'] ?? '',
                'phone' => $store['phone'] ?? '',
                'email' => $store['email'] ?? '',
                'address1' => $store['address1'] ?? '',
                'address2' => $store['address2'] ?? '',
                'city' => $store['city'] ?? '',
                'state' => $store['province_code'] ?? ($store['province'] ?? ''),
                'zip' => $store['zip'] ?? '',
                'country' => $store['country_code'] ?? ($store['country'] ?? ''),
            ],
        ];

        $locationsQuery = <<<'GQL'
        {
            locations(first: 50) {
                edges {
                    node {
                        id
                        name
                        isActive
                        address {
                            address1
                            address2
                            city
                            province
                            provinceCode
                            country
                            countryCode
                            zip
                            phone
                        }
                    }
                }
            }
        }
        GQL;

        $locationsResult = $client->graphqlJson($locationsQuery);
        $edges = $locationsResult['data']['locations']['edges'] ?? [];

        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;
            if (!$node || empty($node['isActive'])) {
                continue;
            }

            $address = $node['address'] ?? [];
            $addresses[] = [
                'id' => $node['id'],
                'source' => 'location',
                'name' => $node['name'] ?? 'Location',
                'label' => 'Location: ' . ($node['name'] ?? 'Unnamed'),
                'company_name' => $node['name'] ?? '',
                'phone' => $address['phone'] ?? ($store['phone'] ?? ''),
                'email' => $store['email'] ?? '',
                'address1' => $address['address1'] ?? '',
                'address2' => $address['address2'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['provinceCode'] ?? ($address['province'] ?? ''),
                'zip' => $address['zip'] ?? '',
                'country' => $address['countryCode'] ?? ($address['country'] ?? ''),
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if (!$shop) {
            return response()->json(['status' => false, 'message' => 'Shop not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'address_source' => 'required|in:store,location',
            'location_id' => 'nullable|string|max:255',
            'location_name' => 'nullable|string|max:255',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:100',
            'default_template_id' => 'nullable|integer',
            'include_barcode' => 'nullable',
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!empty($request->default_template_id)) {
            $templateExists = LabelTemplate::where('user_id', $shop->id)
                ->where('id', $request->default_template_id)
                ->exists();

            if (!$templateExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected default template was not found for this shop',
                ], 422);
            }
        }

        $settings = LabelSetting::firstOrNew(['user_id' => $shop->id]);

        $settings->fill([
            'company_name' => $request->input('company_name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'website' => $request->input('website'),
            'address_source' => $request->input('address_source', 'store'),
            'location_id' => $request->input('address_source') === 'location'
                ? $request->input('location_id')
                : null,
            'location_name' => $request->input('location_name'),
            'address1' => $request->input('address1'),
            'address2' => $request->input('address2'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'zip' => $request->input('zip'),
            'country' => $request->input('country'),
            'default_template_id' => $request->input('default_template_id') ?: null,
            'include_barcode' => filter_var($request->input('include_barcode', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        if ($request->boolean('remove_logo') && !empty($settings->logo_path)) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if (!empty($settings->logo_path)) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            Storage::disk('public')->makeDirectory('label_logos');
            $settings->logo_path = $request->file('logo')->store('label_logos', 'public');
        }

        $settings->save();

        return response()->json([
            'status' => true,
            'message' => 'Label settings saved successfully',
            'data' => $settings->fresh(),
        ]);
    }

    private function seedFromShopifyShop(User $shop): LabelSetting
    {
        $shopInfo = ShopifyAdminClient::for($shop)->getJson('shop.json');
        $store = $shopInfo['shop'] ?? [];

        return LabelSetting::create([
            'user_id' => $shop->id,
            'company_name' => $store['name'] ?? null,
            'phone' => $store['phone'] ?? null,
            'email' => $store['email'] ?? null,
            'address_source' => 'store',
            'location_name' => 'Store address',
            'address1' => $store['address1'] ?? null,
            'address2' => $store['address2'] ?? null,
            'city' => $store['city'] ?? null,
            'state' => $store['province_code'] ?? ($store['province'] ?? null),
            'zip' => $store['zip'] ?? null,
            'country' => $store['country_code'] ?? ($store['country'] ?? null),
            'include_barcode' => true,
        ]);
    }
}
