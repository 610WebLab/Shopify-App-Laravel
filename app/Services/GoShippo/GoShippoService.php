<?php

namespace App\Services\GoShippo;

use App\Interfaces\ShippingServiceInterface;
use GuzzleHttp\Client;
use App\Services\GoShippo\Traits\GoShippoHelper;
use Log;

class GoShippoService implements ShippingServiceInterface
{
    use GoShippoHelper;
    protected $client;
    public function __construct() {
        $this->client = new Client(['base_uri' => 'https://api.usps.com/']);
    
    }

    public function getShippingRate(array $shipmentDetails, array $checkOutData): array
    {
        $rates = $this->handleGoShippoPayload($shipmentDetails, $checkOutData);
        return $rates;
    }
    public function trackShipment(string $trackingNumber): array
    {
        // Implement USPS API call
        $response = $this->client->get("track/{$trackingNumber}");
        return json_decode($response->getBody(), true);
    }
}
