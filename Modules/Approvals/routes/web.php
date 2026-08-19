<?php

use Illuminate\Support\Facades\Route;
use Modules\Approvals\Http\Controllers\ApprovalDataController;
use Modules\Approvals\Http\Controllers\ApprovalsController;
use Modules\Approvals\Http\Controllers\ApprovalSettingsController;

Route::middleware('ek-web')->prefix('approvals')->group(function () {

    Route::get('/', [ApprovalsController::class, 'index'])->name('approvals.index');
    Route::get('data', [ApprovalsController::class, 'dataTable'])->name('approvals.data')->middleware('perm:approvals,read');

    // ---- Approvals (leaf: approvals.data) ----
    Route::controller(ApprovalDataController::class)->prefix('data')->name('approvals.data.')->group(function () {
        Route::middleware('perm:approvals.data,read')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('data', 'dataTable')->name('data');
            Route::get('{id}', 'show')->name('show')->whereUuid('id');
        });

        Route::middleware('perm:approvals.data,read_write')->group(function () {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store')->middleware('audit.bff');
            Route::get('{id}/edit', 'edit')->name('edit')->whereUuid('id');
            Route::put('{id}', 'update')->name('update')->whereUuid('id')->middleware('audit.bff');
        });
    });

    // ---- Roles (leaf: approvals.settings) ----
    Route::controller(ApprovalSettingsController::class)->prefix('settings')->name('approvals.settings.')->group(function () {
        Route::middleware('perm:approvals.settings,read')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('data', 'dataTable')->name('data');
            Route::get('{id}', 'show')->name('show')->whereUuid('id');
        });
        Route::middleware('perm:approvals.settings,read_write')->group(function () {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store')->middleware('audit.bff');
            Route::get('{id}/edit', 'edit')->name('edit')->whereUuid('id');
        });
        Route::middleware('perm:approvals.settings,full_control')
            ->delete('{id}', 'destroy')->name('destroy')->whereUuid('id')->middleware('audit.bff');

    });


});
