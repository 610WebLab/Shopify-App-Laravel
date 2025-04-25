<?php 
namespace App\Services\EasyPost;

use App\Interfaces\ShippingServiceInterface;
use GuzzleHttp\Client;
use App\Services\EasyPost\Traits\EasyPostHelper;
use Log;
class EasyPostService implements ShippingServiceInterface
{
    use EasyPostHelper;
    protected $client;
    public function __construct() {
        $this->client = new Client(['base_uri' => 'https://api.usps.com/']);
    
    }
    public function getShippingRate(array $shipmentDetails, array $checkOutData): array
    {
        $rates = $this->handleEasyPostPayload($shipmentDetails, $checkOutData);
        return $rates;
    }

    public function trackShipment(string $trackingNumber): array
    {
        // Implement USPS API call
        $response = $this->client->get("track/{$trackingNumber}");
        return json_decode($response->getBody(), true);
    }
}