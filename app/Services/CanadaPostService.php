<?php
namespace App\Services;

use App\Interfaces\ShippingServiceInterface;
use GuzzleHttp\Client;

class CanadaPostService implements ShippingServiceInterface
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.canadapost.ca/']);
    }

    public function getShippingRate(array $shipmentDetails): array
    {
        // Implement Canada Post API call
        $response = $this->client->post('rate', [
            'json' => $shipmentDetails
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['rate'];
    }

    public function trackShipment(string $trackingNumber): array
    {
        // Implement Canada Post API call
        $response = $this->client->get("track/{$trackingNumber}");
        return json_decode($response->getBody(), true);
    }
}