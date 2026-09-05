<?php

namespace App\Shopify;

use Gnikyt\BasicShopifyAPI\BasicShopifyAPI;
use Gnikyt\BasicShopifyAPI\Options;
use Gnikyt\BasicShopifyAPI\Session;
use Osiset\ShopifyApp\Util;

class ApiInitializer
{
    public static function initialize(Options $options, ?Session $session = null, array $request = []): BasicShopifyAPI
    {
        if (app()->environment(['local', 'development', 'testing'])) {
            $options->setGuzzleOptions([
                'verify' => false,
            ]);
        }

        $shop = $session?->getShop() ?? ($request['shop'] ?? null);

        $timeStore = Util::getShopifyConfig('api_time_store', $shop);
        $limitStore = Util::getShopifyConfig('api_limit_store', $shop);
        $deferrer = Util::getShopifyConfig('api_deferrer', $shop);

        $api = new BasicShopifyAPI(
            $options,
            new $timeStore(),
            new $limitStore(),
            new $deferrer()
        );

        if ($session !== null) {
            $api->setSession($session);
        }

        return $api;
    }
}
