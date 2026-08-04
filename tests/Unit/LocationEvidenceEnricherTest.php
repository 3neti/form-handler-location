<?php

use Illuminate\Support\Facades\Http;
use LBHurtado\FormHandlerLocation\Exceptions\LocationEvidenceUnavailable;
use LBHurtado\FormHandlerLocation\Services\LocationEvidenceEnricher;

it('enriches coordinates without exposing provider credentials', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.opencagedata.com/*' => Http::response([
            'results' => [[
                'formatted' => 'General Santos City, Philippines',
                'components' => [
                    'city' => 'General Santos City',
                    'state' => 'Soccsksargen',
                    'country' => 'Philippines',
                ],
            ]],
        ]),
        'api.mapbox.com/*' => Http::response('png-bytes', 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);

    $evidence = app(LocationEvidenceEnricher::class)->enrich(6.088770, 125.152582);

    expect($evidence)
        ->toMatchArray([
            'address' => [
                'formatted' => 'General Santos City, Philippines',
                'city' => 'General Santos City',
                'state' => 'Soccsksargen',
                'country' => 'Philippines',
            ],
            'map' => 'data:image/png;base64,'.base64_encode('png-bytes'),
            'map_mime_type' => 'image/png',
        ])
        ->and(json_encode($evidence))
        ->not->toContain('test_key')
        ->not->toContain('test_mapbox_token');
});

it('fails safely when the location capability is not configured', function () {
    Http::preventStrayRequests();
    config()->set('location-handler.opencage_api_key', null);

    expect(fn () => app(LocationEvidenceEnricher::class)->enrich(6.088770, 125.152582))
        ->toThrow(
            LocationEvidenceUnavailable::class,
            'Location evidence is not configured for this deployment.',
        );
});
