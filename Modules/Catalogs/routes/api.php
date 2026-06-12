<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalogs\Http\Controllers\CatalogsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('catalogs', CatalogsController::class)->names('catalogs');
});
