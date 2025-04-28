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
use App\Http\Controllers\V1\RatesByDistanceController;

use App\Http\Controllers\CountriesController;
use App\Http\Controllers\TestShippingController;
use App\Http\Controllers\OtherCarrierServiceController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\LabelTemplateController;
use App\Http\Controllers\DimensionController;
use App\Http\Controllers\PlanController;



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
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Method: POST, GET, OPTIONS, PUT, DELETE');
// header('Access-Control-Allow-Header: Content-Type, X-Auth-Token, Origin Authorization ');


Route::get('/privacy', function () {
    return view('privacy-policy');
});
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);

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
})->middleware(['verify.shopify', 'billable'])->name('home');

Route::get('/pages/{page}', function (Request $request) {
    $request->query('shop');
    $host = $request->query('host');
    $api_key = $request->query('api_key');
    return view('welcome', [
            'shop' => $request->query('shop'),
            'host' => $host,
            'appUrl' =>env('APP_URL'),
            'apiKey' => env('SHOPIFY_API_KEY')
        ]);
});

Route::post('/shipping/rate', [TestShippingController::class, 'calculateRate']);
Route::get('/countries',[CountriesController::class, 'index']);
Route::get('/countryAndState',[CountriesController::class, 'getCountryAndState']);
Route::get('/shippingRates',[ShippingZones::class, 'calCulateTableRateShipping']);
Route::post('/updateChekout',[ShippingZones::class, 'updateCheckoutApi']);

Route::resource('shipzone', ShippingZones::class)->only([
    'index' ,'store' , 'destroy' ,  'show'
]);
Route::get('/get-shipping-zone',[ShippingZones::class,'getShippingzoneOptions']);
// Route::resource('shipmethod/{id}', ShippingZones::class)->only([
//     'index' ,'store' , 'destroy' ,  'show'
// ]);
Route::get('/store-country',[CountriesController::class, 'storeCountryAndState']);
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
    Route::resource('rates_by_distance', RatesByDistanceController::class)->only([
        'show','store','update','destroy'
    ]);

    Route::get('/locations', [RatesByDistanceController::class, 'getShopLocations']);
});
Route::post('/flatRateShip', [ShippingMethod::class, 'flatRateShip']);
Route::post('/freeShipping', [ShippingMethod::class, 'freeShipping']);
Route::resource('/orders',OrderController::class);
Route::resource('/other-carrier-service',OtherCarrierServiceController::class);
Route::get('/update-other-carrier-status/{id}', [OtherCarrierServiceController::class, 'updateOtherCarrierStatus']);
Route::resource('/carrier',CarrierController::class);
Route::get('/update-carrier-status/{id}', [CarrierController::class, 'updateCarrierStatus']);
Route::resource('label-templates', LabelTemplateController::class);
Route::get('/get-templates', [LabelTemplateController::class,'getTemplates']);
Route::post('label-templates/{id}/generate-pdf', [LabelTemplateController::class, 'generatePdf']);
Route::resource('dimension', DimensionController::class);
Route::post('/sort/dimension/{id}', [DimensionController::class, 'updateDimensionSorting']);
Route::get('/get-shpping-rates',[OrderController::class,'getShppingRates']);
Route::get('/print-label/{id}',[OrderController::class,'printLabel']);
Route::get('/order-fulfilled/{id}',[OrderController::class,'orderFulfilled']);

Route::middleware(['monthly_orders'])->group(function () {
    
    Route::post('/create-shipping-label', [OrderController::class, 'createShippingLabel']);
    Route::post('/create-local-shipping-label',[OrderController::class,'createLocalShippingLabel']);


});

Route::get('get_plan', [PlanController::class, "getShopifyPlans"]);
Route::post('/update_free_plan/{id}', [PlanController::class, "updateShopifyPlans"]);

