<?php

    namespace App\Interfaces;

    interface ShippingServiceInterface
    {
        public function getShippingRate(array $shipmentDetails, array $checkOutData): array;
        public function trackShipment(string $trackingNumber): array;
    }
