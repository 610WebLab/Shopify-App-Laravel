<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class PlanController extends Controller
{

    public function getShopifyPlans(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if ($shop) {

            $plans = DB::table('plans')->get();
            $activePlans =  DB::table('charges')->where('user_id', $shop->id)->where('status', 'ACTIVE')->first();

            return response()->json(['status' => true, 'plans' => $plans, 'activePlan' => $shop->plan_id]);
        }
    }

    public function updateShopifyPlans(Request $request, $id)
    {
        $request->validate([
            'shop' => 'required|string',
        ]);
        $user = User::where('name', $request->shop)->first();

        if ($user) {
            DB::table('charges')
                ->where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'CANCELLED']);
            $user->plan_id = $id;
            if ($user->save()) {
                $plans = DB::table('plans')->get();
                return response()->json([
                    'status' => true,
                    'plans' => $plans,
                    'activePlan' => $user->plan_id
                ]);
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to update the plan.'], 500);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Shop not found.'], 404);
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
