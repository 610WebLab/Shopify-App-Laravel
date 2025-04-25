<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShippingZones;
use App\Http\Controllers\CountriesController;
use App\Http\Controllers\OrderController;



// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Method: POST, GET, OPTIONS, PUT, DELETE');
// header('Access-Control-Allow-Header: Origin Authorization');
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'v1'], function () {
    Route::any('/carrier_service', [ShippingZones::class,'apiResonse'])->name('shipping-rates-webhook');
    Route::any('/shipping_method', [ShippingZones::class,'testTableRateShipping']);
    Route::any('/print', [OrderController::class,'printOrderDetailsPackingSlip']);
    Route::any('/template', [OrderController::class,'getTemplatesForOrderDetailPage']);
    // Route::any('/countries',[CountriesController::class, 'index']);
    // Route::get('/orders', function() {
    //     die("weldomeoe";)
    // });
});