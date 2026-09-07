<?php

namespace App\Traits\GraphQl;

use App\Services\Shopify\ShopifyAdminClient;

trait ShopifyGetInventoryItemTrait
{
    public function fetchShopifyInventoryItemById($inventoryItemId, $shop)
    {
        $query = '
        query {
            inventoryItem(id: "gid://shopify/InventoryItem/' . $inventoryItemId . '") {
                id
                tracked
                sku
                inventoryLevels(first: 10) {
                    edges {
                        node {
                            id
                            location {
                                id
                                isActive
                            }
                        }
                    }
                }
                variant {
                    inventoryQuantity
                }
            }
        }';

        return ShopifyAdminClient::for($shop)->graphqlJson($query);
    }
}
