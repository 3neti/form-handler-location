<?php

declare(strict_types=1);

namespace LBHurtado\FormHandlerLocation\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use LBHurtado\FormHandlerLocation\Exceptions\LocationEvidenceUnavailable;

final readonly class LocationEvidenceEnricher
{
    public function __construct(private LocationCapabilityReadiness $readiness) {}

    /**
     * @return array{
     *     address: array{formatted: ?string, city: ?string, state: ?string, country: ?string},
     *     map: string,
     *     map_mime_type: string
     * }
     */
    public function enrich(float $latitude, float $longitude): array
    {
        $readiness = $this->readiness->inspect();

        if (! $readiness['ready']) {
            throw LocationEvidenceUnavailable::because(
                'Location evidence is not configured for this deployment.'
            );
        }

        return [
            'address' => $this->reverseGeocode($latitude, $longitude),
            ...$this->mapEvidence($latitude, $longitude, $readiness['map_provider']),
        ];
    }

    /**
     * @return array{formatted: ?string, city: ?string, state: ?string, country: ?string}
     */
    private function reverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout($this->connectTimeout())
                ->timeout($this->requestTimeout())
                ->retry([200, 500], throw: false)
                ->get((string) config('location-handler.opencage_endpoint'), [
                    'q' => "{$latitude},{$longitude}",
                    'key' => (string) config('location-handler.opencage_api_key'),
                    'no_annotations' => 1,
                    'limit' => 1,
                ]);
        } catch (ConnectionException) {
            throw LocationEvidenceUnavailable::because(
                'Address lookup is temporarily unavailable. Please retry.'
            );
        }

        if (! $response->successful()) {
            throw LocationEvidenceUnavailable::because(
                'Address lookup is temporarily unavailable. Please retry.'
            );
        }

        $result = $response->json('results.0');

        if (! is_array($result) || trim((string) ($result['formatted'] ?? '')) === '') {
            throw LocationEvidenceUnavailable::because(
                'No address could be resolved for the captured coordinates.'
            );
        }

        $components = is_array($result['components'] ?? null) ? $result['components'] : [];

        return [
            'formatted' => $this->nullableString($result['formatted'] ?? null),
            'city' => $this->nullableString(
                $components['city'] ?? $components['town'] ?? $components['municipality'] ?? null
            ),
            'state' => $this->nullableString($components['state'] ?? null),
            'country' => $this->nullableString($components['country'] ?? null),
        ];
    }

    /** @return array{map: string, map_mime_type: string} */
    private function mapEvidence(float $latitude, float $longitude, string $provider): array
    {
        $url = $provider === 'mapbox'
            ? $this->mapboxUrl($latitude, $longitude)
            : $this->googleUrl($latitude, $longitude);

        try {
            $response = Http::accept('image/png,image/jpeg')
                ->connectTimeout($this->connectTimeout())
                ->timeout($this->requestTimeout())
                ->retry([200, 500], throw: false)
                ->get($url);
        } catch (ConnectionException) {
            throw LocationEvidenceUnavailable::because(
                'The location map is temporarily unavailable. Please retry.'
            );
        }

        $mimeType = strtolower(trim(strtok($response->header('Content-Type'), ';') ?: ''));

        if (! $response->successful() || ! in_array($mimeType, ['image/png', 'image/jpeg'], true)) {
            throw LocationEvidenceUnavailable::because(
                'The location map is temporarily unavailable. Please retry.'
            );
        }

        return [
            'map' => 'data:'.$mimeType.';base64,'.base64_encode($response->body()),
            'map_mime_type' => $mimeType,
        ];
    }

    private function mapboxUrl(float $latitude, float $longitude): string
    {
        $token = rawurlencode((string) config('location-handler.mapbox_token'));

        return sprintf(
            'https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/pin-s+ff0000(%1$s,%2$s)/%1$s,%2$s,16,0/600x300@2x?access_token=%3$s',
            $longitude,
            $latitude,
            $token,
        );
    }

    private function googleUrl(float $latitude, float $longitude): string
    {
        return 'https://maps.googleapis.com/maps/api/staticmap?'.http_build_query([
            'center' => "{$latitude},{$longitude}",
            'zoom' => 16,
            'size' => '600x300',
            'markers' => "color:red|{$latitude},{$longitude}",
            'key' => (string) config('location-handler.google_maps_api_key'),
        ]);
    }

    private function connectTimeout(): int
    {
        return max(1, (int) config('location-handler.connect_timeout', 5));
    }

    private function requestTimeout(): int
    {
        return max(1, (int) config('location-handler.request_timeout', 10));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
