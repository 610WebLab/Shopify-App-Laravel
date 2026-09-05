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
use App\Traits\SyncsShopifyOrderTrait;
use Log;

class OrdersUpdatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SyncsShopifyOrderTrait;

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
            set_time_limit(0);

            $this->shopDomain = ShopDomain::fromNative($this->shopDomain);
            $shop = User::where('name', $this->shopDomain->toNative())->first();

            if (!$shop) {
                Log::error('Order Update Webhook: Shop not found', ['shopDomain' => $this->shopDomain->toNative()]);
                return;
            }

            Log::info('Order Update Webhook', ['shop' => $shop->name, 'orderId' => $this->data->id]);

            $this->syncShopifyOrder($shop, $this->data);

            Log::info('Order successfully updated', ['orderId' => $this->data->id]);
        } catch (\Exception $e) {
            Log::error('Error processing order update webhook', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'shopDomain' => is_object($this->shopDomain) ? $this->shopDomain->toNative() : $this->shopDomain,
                'orderId' => $this->data->id ?? null,
            ]);
        }
    }
}
