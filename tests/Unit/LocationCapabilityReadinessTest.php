<?php

use LBHurtado\FormHandlerLocation\Services\LocationCapabilityReadiness;

it('requires reverse geocoding and the selected map provider', function () {
    config()->set('location-handler.opencage_api_key', null);
    config()->set('location-handler.map_provider', 'mapbox');
    config()->set('location-handler.mapbox_token', null);

    $readiness = app(LocationCapabilityReadiness::class)->inspect();

    expect($readiness)
        ->toMatchArray([
            'ready' => false,
            'status' => 'unavailable',
            'map_provider' => 'mapbox',
        ])
        ->and($readiness['missing'])
        ->toBe(['OPENCAGE_API_KEY', 'MAPBOX_TOKEN']);
});

it('reports ready without exposing credentials', function () {
    config()->set('location-handler.opencage_api_key', 'secret-opencage');
    config()->set('location-handler.map_provider', 'mapbox');
    config()->set('location-handler.mapbox_token', 'secret-mapbox');

    $readiness = app(LocationCapabilityReadiness::class)->inspect();

    expect($readiness)
        ->toMatchArray([
            'ready' => true,
            'status' => 'ready',
            'missing' => [],
            'map_provider' => 'mapbox',
        ])
        ->and(json_encode($readiness))
        ->not->toContain('secret-opencage')
        ->not->toContain('secret-mapbox');
});
