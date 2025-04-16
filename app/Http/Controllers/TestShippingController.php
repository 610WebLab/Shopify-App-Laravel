<?php

namespace App\Http\Controllers;

use App\Interfaces\ShippingServiceInterface;
use Illuminate\Http\Request;
use App\Resolvers\ShippingServiceResolver;
use Exception;

class TestShippingController extends Controller 
{

    protected $shippingServiceResolver;

    public function __construct(ShippingServiceResolver $shippingServiceResolver)
    {
        $this->shippingServiceResolver = $shippingServiceResolver;
    }


    public function calculateRate(Request $request)
    {
        try {
            $service = $request->input('service'); // e.g., 'fedex'
            $packageDetails = $request->input('package');

            $shippingService = $this->shippingServiceResolver->resolve($service);

            $rate = $shippingService->getShippingRate($packageDetails);

            return response()->json(['service' => $service, 'rate' => $rate]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function trackShipment(string $trackingNumber): array {
    
        $trackingDetails = $this->shippingService->trackShipment($trackingNumber);
        return response()->json($trackingDetails);
    }

}
