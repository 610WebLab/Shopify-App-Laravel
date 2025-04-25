<?php 
namespace App\Traits\GraphQl;

use GuzzleHttp\Client;

trait ShopifyGetInventoryItemTrait {
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

        $response = $this->makeGraphQLRequest($query, $shop);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Make the actual request to Shopify GraphQL API.
     *
     * @param string $query
     * @return \Psr\Http\Message\ResponseInterface
     */
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