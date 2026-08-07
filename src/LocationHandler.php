<?php

declare(strict_types=1);

namespace LBHurtado\FormHandlerLocation;

use Illuminate\Http\Request;
use Inertia\Inertia;
use LBHurtado\FormFlowManager\Contracts\FormHandlerInterface;
use LBHurtado\FormFlowManager\Contracts\FormHandlerPreviewInterface;
use LBHurtado\FormFlowManager\Data\FormFlowStepData;
use LBHurtado\FormHandlerLocation\Data\LocationData;

/**
 * Location Handler
 *
 * Captures user's geographic location using browser geolocation API.
 * Supports reverse geocoding and map snapshots.
 */
class LocationHandler implements FormHandlerInterface, FormHandlerPreviewInterface
{
    public function getName(): string
    {
        return 'location';
    }

    public function handle(Request $request, FormFlowStepData $step, array $context = []): array
    {
        // Extract data from 'data' key if present (from form submission)
        $inputData = $request->input('data', $request->all());

        if (is_array($inputData['address'] ?? null)) {
            $inputData['formatted_address'] ??= $inputData['address']['formatted'] ?? null;
            $inputData['address_components'] ??= $inputData['address'];
            unset($inputData['address']);
        }

        // Validate using Laravel's validator directly
        $validated = validator($inputData, [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'formatted_address' => 'nullable|string|max:500',
            'address_components' => 'nullable|array',
            'map' => 'nullable|string', // base64 encoded image
            'accuracy' => 'nullable|numeric|min:0',
        ])->validate();

        $validated['timestamp'] = now()->toIso8601String();

        return LocationData::from($validated)->toArray();
    }

    public function validate(array $data, array $rules): bool
    {
        // Validation handled in handle() method
        return true;
    }

    public function render(FormFlowStepData $step, array $context = [])
    {
        return Inertia::render('form-flow/location/LocationCapturePage', $this->props($step, $context));
    }

    public function preview(FormFlowStepData $step, array $context = []): array
    {
        return ['component' => 'form-flow/location/LocationCapturePage', 'props' => $this->props($step, array_merge($context, ['preview_mode' => true]))];
    }

    protected function props(FormFlowStepData $step, array $context): array
    {
        return [
            'flow_id' => $context['flow_id'] ?? null,
            'step' => (string) ($context['step_index'] ?? 0),
            'config' => array_merge([
                'map_provider' => config('location-handler.map_provider', 'google'),
                'capture_snapshot' => config('location-handler.capture_snapshot', true),
                'require_address' => config('location-handler.require_address', false),
            ], $step->config),
            'ui_variant' => $step->config['ui_variant'] ?? config('form-flow.ui.variant', 'default'),
            'preview_mode' => (bool) ($context['preview_mode'] ?? false),
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'opencage_api_key' => 'nullable|string',
            'map_provider' => 'required|in:mapbox,google',
            'mapbox_token' => 'required_if:map_provider,mapbox|nullable|string',
            'capture_snapshot' => 'boolean',
            'require_address' => 'boolean',
            'ui_variant' => 'nullable|string|in:default,compact,immersive',
        ];
    }
}
