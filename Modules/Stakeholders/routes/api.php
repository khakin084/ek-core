<?php

use Illuminate\Support\Facades\Route;
use Modules\Stakeholders\Http\Controllers\StakeholdersController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('stakeholders', StakeholdersController::class)->names('stakeholders');
});
