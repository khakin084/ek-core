<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ServiceDiscoveryHelper
{
    const CONSUL_HTTP_ADDR = 'http://consul:8500';
    const CACHE_TTL = 60; // 1 minute cache
    const SERVICE_PORT = 80; // All services use port 80 internally

    /**
     * Discover service URL with optimized fallback mechanisms
     */
 public static function discoverService(string $serviceName): string
{
    // Primary: Docker direct
    $dockerUrl = self::dockerDirect($serviceName);
    if (self::isReachable($dockerUrl)) {
        return $dockerUrl;
    }

    // Secondary: Consul DNS
    $dnsUrl = self::dnsDiscovery($serviceName);
    if ($dnsUrl && self::isReachable($dnsUrl)) {
        return $dnsUrl;
    }

    // Tertiary: Consul HTTP API
    $apiUrl = self::apiDiscovery($serviceName);
    if ($apiUrl && self::isReachable($apiUrl)) {
        return $apiUrl;
    }

    Log::error("All discovery methods failed for service: {$serviceName}");

    // Production-only auth fallback
    if (app()->environment('production') && $serviceName === 'auth' && !self::isReachable($dockerUrl)) {
        $configUrl = config('api.auth_server_url');
        if ($configUrl) {
            Log::info("Falling back to config for auth service: {$configUrl}");
            return $configUrl;
        }
    }

    // Return Docker URL anyway as last resort
    return $dockerUrl;
}




    /**
     * Direct Docker networking (preferred method)
     */
    private static function dockerDirect(string $serviceName): string
    {
        return "http://{$serviceName}";
    }

    /**
     * DNS-based service discovery
     */
    private static function dnsDiscovery(string $serviceName): ?string
    {
        try {
            $host = "{$serviceName}.service.consul";
            $records = dns_get_record($host, DNS_A);

            if (!empty($records)) {
                return "http://{$host}";
            }
        } catch (\Exception $e) {
            Log::warning("DNS discovery failed for {$serviceName}: " . $e->getMessage());
        }
        return null;
    }

    /**
     * HTTP API-based service discovery
     */
    private static function apiDiscovery(string $serviceName): ?string
    {
        return Cache::remember("consul_service_{$serviceName}", self::CACHE_TTL, function () use ($serviceName) {
            try {
                $client = new Client([
                    'base_uri' => self::CONSUL_HTTP_ADDR,
                    'timeout' => 2.0
                ]);

                $response = $client->get("/v1/catalog/service/{$serviceName}");
                $services = json_decode($response->getBody(), true);

                if (!empty($services)) {
                    $service = $services[0];
                    $address = $service['ServiceAddress'] ?: $service['Address'];
                    return "http://{$address}:{$service['ServicePort']}";
                }
            } catch (RequestException $e) {
                Log::error("Consul API request failed: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("Service discovery failed: " . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Check if service endpoint is reachable
     */
       private static function isReachable(string $url): bool
    {
        try {
            $client = new Client(['timeout' => 1.0]);
            $response = $client->head($url, ['http_errors' => false]);
            return $response->getStatusCode() < 500;
        } catch (\Exception $e) {
            return false;
        }
    }



    /**
     * Get service URL with endpoint
     */
    public static function serviceUrl(string $serviceName, string $endpoint = ''): string
    {
        $baseUrl = self::discoverService($serviceName);
        return $baseUrl . ($endpoint ? '/' . ltrim($endpoint, '/') : '');
    }
}