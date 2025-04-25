<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Carrier;

class CarrierController extends Controller
{
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
    public function show($id, Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if($shop){

            $carrier = Carrier::where('service_id', $id)->where('user_id', $shop->id)->get();
            return response()->json(['status' =>true, 'data' => $carrier]);
        } else {
            return response()->json(['status' =>false, 'message' => 'Shop not found']);
        }
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

    public function updateCarrierStatus($id, Request $request){
        $shop = User::where('name', $request->shop)->first();
        if($shop){
            $getData = Carrier::where('id', $id)->where('user_id', $shop->id)->first();
            if($getData) {
                $getData->status = $request->status;
                if($getData->save()) {
                    return response()->json(['status' =>true, 'message' =>'Carrier service status update successfully']);
                }else {
                    return response()->json(['status' =>false, 'message' =>'Failed to update carrier service status']);
                }
            } else {
                return response()->json(['status' => false, 'message' => "Carrier service not found"]);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Shop not found']);
        }
    }
}
