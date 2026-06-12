<?php

use Illuminate\Support\Facades\Route;
use Modules\Stakeholders\Http\Controllers\StakeholdersController;

Route::middleware(['ek-web'])->group(function () {
    Route::get('create', [StakeholdersController::class,'create'])->name('stakeholders.create');
});
