<?php 
namespace App\Traits\GraphQl;

use GuzzleHttp\Client;
trait ShopifySetInventoryTrait {
    public function setInventoryQuantity($inventoryItemId, $locationId, $quantity, $reason = 'correction', $ignoreCompareQuantity = true, $shop)
    {
        
        $shopifyApiUrl = "https://".$shop->name."/admin/api/".env('SHOPIFY_API_VERSION')."/graphql.json"; 
        
        $mutation = '
        mutation inventorySetQuantities($input: InventorySetQuantitiesInput!) {
            inventorySetQuantities(input: $input) {
                inventoryAdjustmentGroup {
                    reason
                    referenceDocumentUri
                    changes {
                        name
                        delta
                        quantityAfterChange
                    }
                }
                userErrors {
                    code
                    field
                    message
                }
            }
        }';
        
        $variables = [
            'input' => [
                'name' => 'available',
                'reason' => $reason,
                'ignoreCompareQuantity' => $ignoreCompareQuantity,
                'quantities' => [
                    [
                        'inventoryItemId' => $inventoryItemId,
                        'locationId' => $locationId,
                        'quantity' => $quantity
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $shop->password,
            'Content-Type' => 'application/json',
        ])->post($shopifyApiUrl, [
            'query' => $mutation,
            'variables' => $variables,
        ]);

        // Return response
        return $response->json();
    }
}