<?php

declare(strict_types=1);

namespace LBHurtado\FormHandlerLocation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LBHurtado\FormFlowManager\Services\FormFlowService;
use LBHurtado\FormHandlerLocation\Exceptions\LocationEvidenceUnavailable;
use LBHurtado\FormHandlerLocation\Services\LocationEvidenceEnricher;

final class LocationEvidenceController extends Controller
{
    public function __construct(
        private readonly FormFlowService $flows,
        private readonly LocationEvidenceEnricher $enricher,
    ) {}

    public function __invoke(Request $request, string $flowId): JsonResponse
    {
        $state = $this->flows->getFlowState($flowId);

        abort_if($state === null, 404);

        $currentStep = (int) ($state['current_step'] ?? -1);
        $step = $state['instructions']['steps'][$currentStep] ?? null;

        abort_unless(is_array($step) && ($step['handler'] ?? null) === 'location', 409);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $evidence = $this->enricher->enrich(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
            );
        } catch (LocationEvidenceUnavailable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
                'retryable' => true,
            ], 503);
        }

        return response()->json([
            'data' => $evidence,
        ]);
    }
}
