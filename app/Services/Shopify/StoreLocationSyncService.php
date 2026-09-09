<?php

namespace App\Services\Shopify;

use App\Models\StoreLocation;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class StoreLocationSyncService
{
    /**
     * Sync active Shopify locations into store_locations and return active rows.
     */
    public function syncAndList(User $shop): Collection
    {
        $syncedIds = $this->syncFromShopify($shop);

        if (!empty($syncedIds)) {
            StoreLocation::where('user_id', $shop->id)
                ->whereNotIn('shopify_location_id', $syncedIds)
                ->update(['is_active' => false]);
        }

        return StoreLocation::where('user_id', $shop->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string> Synced Shopify location numeric IDs
     */
    public function syncFromShopify(User $shop): array
    {
        $query = <<<'GQL'
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
                            latitude
                            longitude
                        }
                    }
                }
            }
        }
        GQL;

        $result = ShopifyAdminClient::for($shop)->graphqlJson($query);

        if (!empty($result['errors'])) {
            $messages = collect($result['errors'])
                ->map(fn ($error) => $error['message'] ?? json_encode($error))
                ->implode('; ');

            $lower = strtolower($messages);
            if (
                str_contains($lower, 'access denied')
                || str_contains($lower, 'read_locations')
                || str_contains($lower, 'access_denied')
            ) {
                throw new RuntimeException(
                    'Shopify denied locations access. Add the read_locations scope and reinstall/re-authorize the app.'
                );
            }

            throw new RuntimeException('Shopify locations sync failed: ' . $messages);
        }

        $edges = $result['data']['locations']['edges'] ?? null;
        if ($edges === null) {
            throw new RuntimeException(
                'Shopify returned no locations data. Confirm the app has read_locations and the shop was re-authorized.'
            );
        }

        $syncedIds = [];

        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;
            if (!$node || empty($node['isActive'])) {
                continue;
            }

            $gid = $node['id'] ?? '';
            $shopifyLocationId = $this->extractNumericId($gid);
            if ($shopifyLocationId === '') {
                continue;
            }

            $address = $node['address'] ?? [];

            StoreLocation::updateOrCreate(
                [
                    'user_id' => $shop->id,
                    'shopify_location_id' => $shopifyLocationId,
                ],
                [
                    'shopify_location_gid' => $gid,
                    'name' => $node['name'] ?? '',
                    'address1' => $address['address1'] ?? null,
                    'address2' => $address['address2'] ?? null,
                    'city' => $address['city'] ?? null,
                    'province' => $address['province'] ?? null,
                    'province_code' => $address['provinceCode'] ?? null,
                    'country' => $address['country'] ?? null,
                    'country_code' => $address['countryCode'] ?? null,
                    'zip' => $address['zip'] ?? null,
                    'phone' => $address['phone'] ?? null,
                    'latitude' => $address['latitude'] ?? null,
                    'longitude' => $address['longitude'] ?? null,
                    'is_active' => true,
                ]
            );

            $syncedIds[] = $shopifyLocationId;
        }

        return $syncedIds;
    }

    private function extractNumericId(string $gid): string
    {
        if ($gid === '') {
            return '';
        }

        if (preg_match('/(\d+)$/', $gid, $matches)) {
            return $matches[1];
        }

        return $gid;
    }
}
