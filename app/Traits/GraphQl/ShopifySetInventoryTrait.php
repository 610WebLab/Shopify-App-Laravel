<?php

namespace App\Traits\GraphQl;

use App\Services\Shopify\ShopifyAdminClient;

trait ShopifySetInventoryTrait
{
    public function setInventoryQuantity($inventoryItemId, $locationId, $quantity, $reason = 'correction', $ignoreCompareQuantity = true, $shop)
    {
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

        return ShopifyAdminClient::for($shop)->graphqlJson($mutation, $variables);
    }
}
