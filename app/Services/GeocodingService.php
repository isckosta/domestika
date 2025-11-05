<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.geocoding.api_key', '');
    }

    /**
     * Geocode an address to coordinates using a geocoding service.
     * For now, using Nominatim (OpenStreetMap) as default, but can be configured for Google Maps, etc.
     */
    public function geocodeAddress(string $address): ?array
    {
        try {
            // Using Nominatim (OpenStreetMap) - free and no API key required
            $response = Http::timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

            if ($response->successful() && $response->json()) {
                $data = $response->json()[0] ?? null;

                if ($data) {
                    return [
                        'latitude' => (float) $data['lat'],
                        'longitude' => (float) $data['lon'],
                        'address' => $data['display_name'] ?? $address,
                    ];
                }
            }

            Log::warning('Geocoding failed', ['address' => $address]);

            return null;
        } catch (\Exception $e) {
            Log::error('Geocoding error', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Calculate distance between two coordinates in kilometers.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

