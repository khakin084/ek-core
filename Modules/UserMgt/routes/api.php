<?php

use Illuminate\Support\Facades\Route;
use Modules\UserMgt\Http\Controllers\UserMgtController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('usermgts', UserMgtController::class)->names('usermgt');
});
