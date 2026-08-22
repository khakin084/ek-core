<?php

use Illuminate\Support\Facades\Route;
use Modules\Stakeholders\Http\Controllers\StakeholdersController;

Route::middleware('ek-web')->prefix('stakeholders')->group(function () {
    // ---- Stakeholders (leaf: stakeholders) ----
    Route::controller(StakeholdersController::class)->name('stakeholders.')->group(function () {
        Route::middleware('perm:stakeholders,read')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('data', 'dataTable')->name('data');
            Route::get('{id}', 'show')->name('show')->whereUuid('id');
        });

        Route::middleware('perm:stakeholders,read_write')->group(function () {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store')->middleware('audit.bff');
            Route::get('{id}/edit', 'edit')->name('edit')->whereUuid('id');
            Route::put('{id}', 'update')->name('update')->whereUuid('id')->middleware('audit.bff');
        });
    });
});

