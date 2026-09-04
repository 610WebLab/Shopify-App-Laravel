<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Util;
use RuntimeException;
use App\Models\User;

class BillableAPI
{
    /**
     * Checks if a shop has paid for access.
     *
     * @param Request $request The request object.
     * @param Closure $next The next action.
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Util::useNativeAppBridge() === false) {
            throw new RuntimeException('You cannot use Billable middleware with SPA mode');
        }

        if (Util::getShopifyConfig('billing_enabled') === true) {
            /** @var IShopModel $shop */
            $shop = auth()->user();
            if (!$shop->plan && !$shop->isFreemium() && !$shop->isGrandfathered()) {
                if ((int) $shop->plan_id < 1) {
                    $defaultPlanId = DB::table('plans')
                        ->where('on_install', true)
                        ->value('id')
                        ?? DB::table('plans')->orderBy('id')->value('id');

                    if (!$defaultPlanId) {
                        throw new RuntimeException(
                            'No billing plans found. Run: php artisan db:seed --class=PlansTableSeeder'
                        );
                    }

                    $user = User::find($shop->id);
                    $user->plan_id = $defaultPlanId;
                    $user->save();

                    return $next($request);
                }

                return Redirect::route(
                    Util::getShopifyConfig('route_names.billing'),
                    array_merge($request->input(), [
                        'shop' => $shop->getDomain()->toNative(),
                        'host' => $request->get('host'),
                    ])
                );
            }
        }

        return $next($request);
    }
}
