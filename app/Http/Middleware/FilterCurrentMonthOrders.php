<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;


class FilterCurrentMonthOrders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = User::where('name', $request->shop)->first();

        if (!$user) {
            return response()->json(['status' => false, "message" => "Shop not found"]);
        }

        $currentMonth = Carbon::now()->month;

        $ordersCount = Order::where('user_id', $user->id)
            ->whereMonth('created_at', $currentMonth)
            ->whereNotNull('label_url')
            ->where('label_url', '!=', '')
            ->count();

        // Plan limits
        $limits = [
            1 => 50,    // Free Plan
            2 => 100,  // Paid Plan
        ];

        if (isset($limits[$user->plan_id]) && $ordersCount >= $limits[$user->plan_id]) {
            return response()->json([
                'status' => false,
                'message' => "Label generation limit exceeded for the current plan. You have reached your monthly limit of {$limits[$user->plan_id]} labels."
            ]);
        }

        return $next($request);
    }
}
