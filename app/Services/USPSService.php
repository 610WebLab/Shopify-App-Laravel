<?php 
namespace App\Services;

use App\Interfaces\ShippingServiceInterface;
use GuzzleHttp\Client;

class USPSService implements ShippingServiceInterface
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.usps.com/']);
    }

    public function getShippingRate(array $shipmentDetails): float
    {
        // Implement USPS API call
        $response = $this->client->post('rate', [
            'json' => $shipmentDetails
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['rate'];
    }

    public function trackShipment(string $trackingNumber): array
    {
        // Implement USPS API call
        $response = $this->client->get("track/{$trackingNumber}");
        return json_decode($response->getBody(), true);
    }
}