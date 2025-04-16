<?php

namespace App\Resolvers;

use Illuminate\Contracts\Container\Container;
use App\Interfaces\ShippingServiceInterface;
use Exception;

class ShippingServiceResolver
{
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function resolve(string $service): ShippingServiceInterface
    {
        $serviceKey = 'shipping.' . strtolower($service);

        if ($this->app->bound($serviceKey)) {
            return $this->app->make($serviceKey);
        }

        throw new Exception("Invalid shipping service: {$service}");
    }
}
