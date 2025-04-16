<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Util;
use RuntimeException;
use Illuminate\Http\Request;
use App\Models\User;

class BillableAPI
{
    /**
     * Checks if a shop has paid for access.
     *
     * @param Request $request The request object.
     * @param Closure $next The next action.
     *
     *@throws Exception
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // dd($request->all(), "custom Billing API");
        if (Util::useNativeAppBridge() === false) {
            throw new RuntimeException('You cannot use Billable middleware with SPA mode');
        }

        if (Util::getShopifyConfig('billing_enabled') === true) {
            /** @var $shop IShopModel */
            $shop = auth()->user();
            if (!$shop->plan && !$shop->isFreemium() && !$shop->isGrandfathered()) {
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing

                if ($shop->plan_id < 1) {
                    $user = User::find($shop->id);
                    $user->plan_id = 1;
                    if($user->save()) {
                        return $next($request);
                    }
                } else {
                    return Redirect::route(
                        Util::getShopifyConfig('route_names.billing'),
                        array_merge($request->input(), [
                            'shop' => $shop->getDomain()->toNative(),
                            'host' => $request->get('host'),
                        ])
                    );
                }
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
