<?php

namespace App\Traits;

use App\Models\Dimension;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Log;

trait SyncsShopifyOrderTrait
{
    protected function syncShopifyOrder(User $shop, object $payload): void
    {
        $lockKey = 'shopify-order-sync:' . $shop->id . ':' . $payload->id;
        $lock = Cache::lock($lockKey, 15);

        try {
            $lock->block(10, function () use ($shop, $payload) {
                $this->persistShopifyOrder($shop, $payload);
            });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning('Shopify order sync lock timeout, retrying once', [
                'shopId' => $shop->id,
                'orderId' => $payload->id,
            ]);
            $this->persistShopifyOrder($shop, $payload);
        }
    }

    protected function persistShopifyOrder(User $shop, object $payload): void
    {
        DB::transaction(function () use ($shop, $payload) {
            $order = Order::where('user_id', $shop->id)
                ->where('order_id', $payload->id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                $order = new Order([
                    'user_id' => $shop->id,
                    'order_id' => $payload->id,
                ]);
                $this->applyOrderCreateDefaults($order, $shop->id, $payload->id);
            }

            $order->fill($this->mapShopifyOrderAttributes($payload));

            try {
                $order->save();
            } catch (QueryException $e) {
                if (!$this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                $existing = Order::where('user_id', $shop->id)
                    ->where('order_id', $payload->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing->fill($this->mapShopifyOrderAttributes($payload));
                $existing->save();
            }
        });
    }

    protected function mapShopifyOrderAttributes(object $payload): array
    {
        $totalQuantity = 0;
        if (isset($payload->line_items)) {
            foreach ($payload->line_items as $lineItem) {
                if (!empty($lineItem->quantity)) {
                    $totalQuantity += $lineItem->quantity;
                }
            }
        }

        $fulfillmentStatus = !empty($payload->cancelled_at)
            ? 'Canceled'
            : ucfirst(!empty($payload->fulfillment_status) ? $payload->fulfillment_status : 'Unfulfilled');

        return [
            'order_name' => $payload->name,
            'order_no' => $payload->order_number,
            'date' => \Carbon\Carbon::parse($payload->created_at)->format('Y-m-d H:i:s'),
            'customer_id' => $payload->customer->id ?? null,
            'customer_name' => trim(($payload->customer->first_name ?? '') . ' ' . ($payload->customer->last_name ?? '')),
            'customer_email' => $payload->customer->email ?? ($payload->email ?? null),
            'total' => $payload->total_price,
            'payment_status' => $payload->financial_status,
            'fullfilement' => $fulfillmentStatus,
            'item_count' => $totalQuantity,
            'tags' => $payload->tags ?? null,
            'shipping_code' => !empty($payload->shipping_lines) ? $payload->shipping_lines[0]->code : '',
        ];
    }

    protected function applyOrderCreateDefaults(Order $order, int $shopId, $orderId): void
    {
        $smallestSortingValue = Dimension::where('user_id', $shopId)->orderBy('sorting', 'ASC')->first();

        if (!$smallestSortingValue) {
            Log::warning('Shopify order sync: No dimensions found for shop', [
                'shopId' => $shopId,
                'orderId' => $orderId,
            ]);
        }

        $order->delivery_status = '';
        $order->delivery_method = '';
        $order->shipping_service = 0;
        $order->carrier_id = null;
        $order->template_id = null;
        $order->dimension_id = $smallestSortingValue->id ?? null;
    }

    protected function isUniqueConstraintViolation(QueryException $e): bool
    {
        $errorInfo = $e->errorInfo[1] ?? null;

        return in_array($errorInfo, [1062, 19], true)
            || str_contains(strtolower($e->getMessage()), 'unique')
            || str_contains(strtolower($e->getMessage()), 'duplicate');
    }
}
