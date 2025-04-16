<?php

namespace App\Traits\GraphQl;

use GuzzleHttp\Client;

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

        $response = $this->makeGraphQLRequest($query, $shop);
        return json_decode($response->getBody()->getContents(), true);
    }

    private function makeGraphQLRequest($query, $shop)
    {
        

        $client = new Client();
       $shopifyUrl = "https://".$shop->name."/admin/api/".env('SHOPIFY_API_VERSION')."/graphql.json";

        return $client->post($shopifyUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $shop->password,
            ],
            'json' => ['query' => $query],
        ]);
    }
}
