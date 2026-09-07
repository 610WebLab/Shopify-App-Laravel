<?php

namespace App\Traits\GraphQl;

use App\Services\Shopify\ShopifyAdminClient;

trait ShopifyGetOrderDetailsTrait
{
    public function fetchShopifyOrderById($orderId, $shop)
    {
        $query = '
        query {
            order(id: "gid://shopify/Order/' . $orderId . '") {
                name
                lineItems(first: 10){
                    edges {
                        node {
                            id
                            name
                            currentQuantity
                            product {
                                id
                                title
                                handle
                                totalInventory
                                variants(first: 10) {
                                    edges {
                                        node {
                                            id
                                            displayName
                                            title
                                            inventoryItem {
                                                id
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }';

        return ShopifyAdminClient::for($shop)->graphqlJson($query);
    }
}
