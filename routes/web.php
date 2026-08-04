<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LBHurtado\FormHandlerLocation\Http\Controllers\LocationEvidenceController;

Route::middleware(['web', 'throttle:30,1'])
    ->post('/form-flow/{flowId}/location/evidence', LocationEvidenceController::class)
    ->name('form-flow.location.evidence');
