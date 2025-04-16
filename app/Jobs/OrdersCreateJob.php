<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;
use App\Models\User;
use App\Models\Order;
use Log;
use App\Models\Dimension;
class OrdersCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's myshopify domain
     *
     * @var ShopDomain|string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Set an unlimited execution time
            set_time_limit(0);

            // Convert domain
            $this->shopDomain = ShopDomain::fromNative($this->shopDomain);
            $shop = User::where('name', $this->shopDomain->toNative())->first();

            if (!$shop) {
                Log::error('Order Create Webhook: Shop not found', ['shopDomain' => $this->shopDomain->toNative()]);
                return;
            }

            Log::info('Order Create Webhook', ['shop' => $shop, 'orderId' => $this->data->id]);
            $smallestSortingValue = Dimension::where('user_id', $shop->id)->orderBy('sorting', 'ASC')->first();
            $totalQuantity = 0;
            if (isset($this->data->line_items)) {
                foreach ($this->data->line_items as $lineItem) {
                    if (!empty($lineItem->quantity)) {
                        $totalQuantity += $lineItem->quantity;
                    }
                }
            }

            Order::updateOrCreate(
                [
                    'user_id' => $shop->id,
                    'order_id' => $this->data->id
                ],
                [
                    'order_name' => $this->data->name,
                    'order_no' => $this->data->order_number,
                    'email' => $this->data->email,
                    'date' => \Carbon\Carbon::parse($this->data->created_at)->format('Y-m-d H:i:s'),
                    'customer_id' => $this->data->customer->id ?? null,
                    'customer_name' => trim(($this->data->customer->first_name ?? '') . ' ' . ($this->data->customer->last_name ?? '')),
                    'customer_email' => $this->data->customer->email ?? null,
                    'total' => $this->data->total_price,
                    'payment_status' => $this->data->financial_status,
                    'fullfilement' => ucfirst(!empty($this->data->fulfillment_status) ? $this->data->fulfillment_status : "Unfulfilled"),
                    'item_count' => $totalQuantity,
                    'delivery_status' => "",
                    'delivery_method' => "",
                    'tags' => $this->data->tags ?? null,
                    'shipping_code' => !empty($this->data->shipping_lines) ? $this->data->shipping_lines[0]->code : "",
                    'shipping_service' => 0,
                    'carrier_id' => null,
                    'template_id' => null,
                    'dimension_id'=> $smallestSortingValue->id
                ]
            );

            Log::info('Order successfully processed', ['orderId' => $this->data->id]);
        } catch (\Exception $e) {
            Log::error('Error processing order webhook', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'shopDomain' => $this->shopDomain->toNative(),
                'orderId' => $this->data->id ?? null
            ]);
        }
    }
}
