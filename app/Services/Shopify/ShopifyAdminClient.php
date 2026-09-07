<?php

namespace App\Services\Shopify;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ShopifyAdminClient
{
    protected object $shop;

    public function __construct(object $shop)
    {
        $this->shop = $shop;
    }

    public static function for(object $shop): self
    {
        return new self($shop);
    }

    public function apiVersion(): string
    {
        return trim((string) config('shopify-app.api_version', '2024-04'), '"');
    }

    public function baseUrl(): string
    {
        return 'https://' . $this->shop->name . '/admin/api/' . $this->apiVersion();
    }

    /**
     * Shared HTTP client for all Shopify Admin API calls.
     * SSL verification is controlled by shopify-app.http_verify_ssl.
     */
    public function http(): PendingRequest
    {
        $request = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Shopify-Access-Token' => $this->shop->password,
        ]);

        if (!config('shopify-app.http_verify_ssl')) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    public function url(string $path): string
    {
        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->http()->get($this->url($path), $query);
    }

    public function post(string $path, array $data = []): Response
    {
        return $this->http()->post($this->url($path), $data);
    }

    public function put(string $path, array $data = []): Response
    {
        return $this->http()->put($this->url($path), $data);
    }

    public function graphql(string $query, array $variables = []): Response
    {
        $payload = ['query' => $query];

        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        return $this->http()->post($this->url('graphql.json'), $payload);
    }

    public function getJson(string $path, array $query = []): ?array
    {
        return $this->get($path, $query)->json();
    }

    public function postJson(string $path, array $data = []): ?array
    {
        return $this->post($path, $data)->json();
    }

    public function graphqlJson(string $query, array $variables = []): ?array
    {
        return $this->graphql($query, $variables)->json();
    }
}
