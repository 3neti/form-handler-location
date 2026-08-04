<?php

declare(strict_types=1);

namespace LBHurtado\FormHandlerLocation\Services;

final class LocationCapabilityReadiness
{
    /**
     * @return array{ready: bool, status: string, missing: list<string>, map_provider: string}
     */
    public function inspect(): array
    {
        $mapProvider = trim((string) config('location-handler.map_provider', 'google'));
        $missing = [];

        if (trim((string) config('location-handler.opencage_api_key')) === '') {
            $missing[] = 'OPENCAGE_API_KEY';
        }

        if ($mapProvider === 'mapbox') {
            if (trim((string) config('location-handler.mapbox_token')) === '') {
                $missing[] = 'MAPBOX_TOKEN';
            }
        } elseif ($mapProvider === 'google') {
            if (trim((string) config('location-handler.google_maps_api_key')) === '') {
                $missing[] = 'GOOGLE_MAPS_API_KEY';
            }
        } else {
            $missing[] = 'LOCATION_HANDLER_MAP_PROVIDER';
        }

        return [
            'ready' => $missing === [],
            'status' => $missing === [] ? 'ready' : 'unavailable',
            'missing' => $missing,
            'map_provider' => $mapProvider,
        ];
    }
}
