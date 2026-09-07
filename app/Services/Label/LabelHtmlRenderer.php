<?php

namespace App\Services\Label;

use App\Models\LabelSetting;
use App\Models\LabelTemplate;
use App\Models\Order;
use App\Models\User;
use App\Services\Shopify\ShopifyAdminClient;
use DateTime;
use Milon\Barcode\DNS1D;

class LabelHtmlRenderer
{
    /**
     * Resolve ship-from branding and address for a shop.
     *
     * @return array{from:array,logo_url:string,website:string,include_barcode:bool}
     */
    public function resolveFromContext(User $user): array
    {
        $resolvedFrom = LabelSetting::resolveFromAddress($user);

        if ($resolvedFrom) {
            return [
                'from' => [
                    'name' => $resolvedFrom['name'],
                    'street1' => $resolvedFrom['street1'],
                    'city' => $resolvedFrom['city'],
                    'state' => $resolvedFrom['state'],
                    'zip' => $resolvedFrom['zip'],
                    'country' => $resolvedFrom['country'],
                    'phone' => $resolvedFrom['phone'],
                    'email' => $resolvedFrom['email'],
                ],
                'logo_url' => $resolvedFrom['logo_url'] ?? '',
                'website' => $resolvedFrom['website'] ?? '',
                'include_barcode' => (bool) $resolvedFrom['include_barcode'],
            ];
        }

        $shopInfo = ShopifyAdminClient::for($user)->getJson('shop.json');
        $shop = $shopInfo['shop'] ?? [];

        return [
            'from' => [
                'name' => $shop['name'] ?? '',
                'street1' => $shop['address1'] ?? '',
                'city' => $shop['city'] ?? '',
                'state' => $shop['province_code'] ?? '',
                'zip' => $shop['zip'] ?? '',
                'country' => $shop['country_code'] ?? '',
                'phone' => $shop['phone'] ?? '',
                'email' => $shop['email'] ?? '',
            ],
            'logo_url' => '',
            'website' => $shop['domain'] ?? ($shop['myshopify_domain'] ?? ''),
            'include_barcode' => true,
        ];
    }

    public function buildToAddress(array $shopifyOrder): array
    {
        $shipping = $shopifyOrder['shipping_address'] ?? [];
        $billing = $shopifyOrder['billing_address'] ?? [];
        $source = !empty($shipping) ? $shipping : $billing;

        return [
            'name' => $source['name']
                ?? $source['company']
                ?? ($shopifyOrder['customer']['first_name'] ?? 'Customer'),
            'street1' => $source['address1'] ?? '',
            'city' => $source['city'] ?? '',
            'state' => $source['province_code'] ?? ($source['province'] ?? ''),
            'zip' => $source['zip'] ?? '',
            'country' => $source['country_code'] ?? ($source['country'] ?? ''),
            'phone' => $source['phone'] ?? ($shopifyOrder['phone'] ?? ''),
            'email' => $source['email']
                ?? ($shopifyOrder['email'] ?? ($shopifyOrder['customer']['email'] ?? '')),
        ];
    }

    /**
     * @param  array<int, array>  $lineItems
     */
    public function buildPlaceholders(
        LabelTemplate $template,
        array $fromContext,
        array $toAddress,
        array $lineItems,
        ?Order $localOrder = null,
        array $shopifyOrder = [],
        float $shipPrice = 0
    ): array {
        $totalPrice = 0;
        foreach ($lineItems as $item) {
            $totalPrice += ((float) ($item['price'] ?? 0)) * ((float) ($item['quantity'] ?? 1));
        }

        $firstItem = $lineItems[0] ?? [];
        $orderNumber = $localOrder->order_no
            ?? ($shopifyOrder['name'] ?? ($shopifyOrder['order_number'] ?? 'Preview'));
        $orderDate = null;

        if (!empty($localOrder?->date)) {
            $orderDate = (new DateTime($localOrder->date))->format('d-m-Y');
        } elseif (!empty($shopifyOrder['created_at'])) {
            $orderDate = (new DateTime($shopifyOrder['created_at']))->format('d-m-Y');
        } else {
            $orderDate = (new DateTime())->format('d-m-Y');
        }

        $trackingNumber = $shopifyOrder['fulfillments'][0]['tracking_number']
            ?? ($shopifyOrder['tracking_number'] ?? 'PENDING');

        $barcode = '';
        if ($fromContext['include_barcode']) {
            $dns1d = new DNS1D();
            $barcodeValue = preg_replace('/\D+/', '', (string) $orderNumber) ?: '12345';
            $barcodePng = $dns1d->getBarcodePNG($barcodeValue, 'C128', 2, 70);
            $barcode = 'data:image/png;base64,' . $barcodePng;
        }

        return [
            '{logo_url}' => $fromContext['logo_url'] ?: ($template->logo_url ?? ''),
            '{website}' => $fromContext['website'] ?? '',
            '{order_number}' => (string) $orderNumber,
            '{order_date}' => $orderDate,
            '{tracking_number}' => (string) $trackingNumber,
            '{from_name}' => $fromContext['from']['name'] ?? '',
            '{from_street1}' => $fromContext['from']['street1'] ?? '',
            '{from_city}' => $fromContext['from']['city'] ?? '',
            '{from_state}' => $fromContext['from']['state'] ?? '',
            '{from_zip}' => $fromContext['from']['zip'] ?? '',
            '{from_country}' => $fromContext['from']['country'] ?? '',
            '{from_phone}' => $fromContext['from']['phone'] ?? '',
            '{from_email}' => $fromContext['from']['email'] ?? '',
            '{to_name}' => $toAddress['name'] ?? '',
            '{to_street1}' => $toAddress['street1'] ?? '',
            '{to_city}' => $toAddress['city'] ?? '',
            '{to_state}' => $toAddress['state'] ?? '',
            '{to_zip}' => $toAddress['zip'] ?? '',
            '{to_country}' => $toAddress['country'] ?? '',
            '{to_phone}' => $toAddress['phone'] ?? '',
            '{to_email}' => $toAddress['email'] ?? '',
            '{bar_code}' => $barcode,
            '{total_price}' => number_format($totalPrice + $shipPrice, 2),
            '{items}' => implode(', ', array_map(
                static fn ($item) => ($item['name'] ?? 'Item') . ' (Qty: ' . ($item['quantity'] ?? 1) . ')',
                $lineItems
            )),
            '{item_name}' => $firstItem['name'] ?? 'N/A',
            '{item_price}' => isset($firstItem['price']) ? number_format((float) $firstItem['price'], 2) : '',
            '{item_quantity}' => (string) ($firstItem['quantity'] ?? ''),
            '{item_weight}' => isset($firstItem['grams']) ? (string) $firstItem['grams'] : '',
        ];
    }

    public function fillTemplate(LabelTemplate $template, array $placeholders): string
    {
        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template->content
        );
    }

    /**
     * @return array{html:string,order:?Order,order_label:string}
     */
    public function renderPreviewHtml(LabelTemplate $template, User $user): array
    {
        $fromContext = $this->resolveFromContext($user);
        $localOrder = Order::where('user_id', $user->id)->orderByDesc('id')->first();
        $shopifyOrder = [];

        if ($localOrder && !empty($localOrder->order_id)) {
            $response = ShopifyAdminClient::for($user)->get('orders/' . $localOrder->order_id . '.json');
            if ($response->successful()) {
                $shopifyOrder = $response->json('order') ?? [];
            }
        }

        if (empty($shopifyOrder)) {
            $shopifyOrder = $this->sampleShopifyOrder($fromContext, $localOrder);
        }

        $toAddress = $this->buildToAddress($shopifyOrder);
        $lineItems = $shopifyOrder['line_items'] ?? [];
        if (empty($lineItems)) {
            $lineItems = $this->sampleLineItems();
        }

        $placeholders = $this->buildPlaceholders(
            $template,
            $fromContext,
            $toAddress,
            $lineItems,
            $localOrder,
            $shopifyOrder
        );

        return [
            'html' => $this->fillTemplate($template, $placeholders),
            'order' => $localOrder,
            'order_label' => $placeholders['{order_number}'],
        ];
    }

    private function sampleShopifyOrder(array $fromContext, ?Order $localOrder): array
    {
        return [
            'name' => $localOrder->order_no ?? '#1001',
            'order_number' => $localOrder->order_no ?? '1001',
            'created_at' => $localOrder->date ?? now()->toIso8601String(),
            'email' => $localOrder->customer_email ?? 'customer@example.com',
            'shipping_address' => [
                'name' => $localOrder->customer_name ?? 'Jordan Avery',
                'address1' => '221B Market Street',
                'city' => 'San Francisco',
                'province_code' => 'CA',
                'zip' => '94105',
                'country_code' => 'US',
                'phone' => '+1 415 555 0199',
                'email' => $localOrder->customer_email ?? 'customer@example.com',
            ],
            'line_items' => $this->sampleLineItems(),
            'tracking_number' => '1Z999AA10123456784',
        ];
    }

    private function sampleLineItems(): array
    {
        return [
            [
                'name' => 'Premium Cotton Tee',
                'quantity' => 2,
                'price' => '29.00',
                'grams' => 250,
            ],
            [
                'name' => 'Gift Box Packaging',
                'quantity' => 1,
                'price' => '5.00',
                'grams' => 100,
            ],
        ];
    }
}
