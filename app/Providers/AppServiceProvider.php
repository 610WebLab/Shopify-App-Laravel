<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FedExService;
use App\Services\USPSService;
use App\Services\CanadaPostService;
use App\Services\EasyPost\EasyPostService;
use App\Services\GoShippo\GoShippoService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind shipping services dynamically
        $this->app->bind('shipping.easypost', EasyPostService::class);
        $this->app->bind('shipping.shippo', GoShippoService::class);
        $this->app->bind('shipping.fedex', FedExService::class);
        $this->app->bind('shipping.usps', USPSService::class);
        $this->app->bind('shipping.canadapost', CanadaPostService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
