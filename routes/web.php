<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ShippingZones;
use App\Http\Controllers\ShippingMethod;
use App\Http\Controllers\V1\LocalPickupMethodController;
use App\Http\Controllers\V1\FlatRateMethodController;
use App\Http\Controllers\V1\FreeShipMethodController;
use App\Http\Controllers\V1\TableRateMethodController;
use App\Http\Controllers\V1\TableRateRowController;
use App\Http\Controllers\CountriesController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Method: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Header: Content-Type, X-Auth-Token, Origin Authorization ');


Route::get('/privacy', function () {
    return view('privacy-policy');
});

Route::get('/', function (Request $request) {
    $request->query('shop');
    $host = $request->query('host');
    $api_key = $request->query('api_key');
    return view('welcome', [
            'shop' => $request->query('shop'),
            'host' => $host,
            'appUrl' =>env('APP_URL'),
            'apiKey' => env('SHOPIFY_API_KEY')
        ]);
})->middleware(['verify.shopify'])->name('home');

Route::get('/countries',[CountriesController::class, 'index']);
Route::get('/countryAndState',[CountriesController::class, 'getCountryAndState']);
Route::get('/shippingRates',[ShippingZones::class, 'calCulateTableRateShipping']);
Route::post('/updateChekout',[ShippingZones::class, 'updateCheckoutApi']);

Route::resource('shipzone', ShippingZones::class)->only([
    'index' ,'store' , 'destroy' ,  'show'
]);
// Route::resource('shipmethod/{id}', ShippingZones::class)->only([
//     'index' ,'store' , 'destroy' ,  'show'
// ]);
Route::get('/updateShipMethodStatus/{id}', [ShippingZones::class, 'updateShipMethodStatus']);
Route::get('/updateShipZoneStatus/{id}', [ShippingZones::class, 'updateShipZoneStatus']);

Route::post('/localPickup', [ShippingMethod::class, 'localPickup']);
Route::resource('shipping-options', ShippingMethod::class)->only([
    'index','store','show', 
]);

Route::prefix('v1')->group(function () {

    Route::resource('local-pickup-shipping', LocalPickupMethodController::class)->only([
        'store','update','show','destroy'
    ]);
    Route::resource('flat-rate-shipping', FlatRateMethodController::class)->only([
        'store','update','show','destroy'
    ]);
    Route::resource('free-rate-shipping', FreeShipMethodController::class)->only([
        'store','update','show','destroy'
    ]);
    Route::resource('table-rate-shipping', TableRateMethodController::class)->only([
        'store','update','show','destroy'
    ]);

    Route::resource('table-rates-rows', TableRateRowController::class)->only([
        'index','store','update','destroy'
    ]);

});

Route::post('/flatRateShip', [ShippingMethod::class, 'flatRateShip']);
Route::post('/freeShipping', [ShippingMethod::class, 'freeShipping']);
