<?php

use Illuminate\Support\Facades\Route;
use Modules\UserMgt\Http\Controllers\AccessControlController;
use Modules\UserMgt\Http\Controllers\RoleController;
use Modules\UserMgt\Http\Controllers\UserController;
use Modules\UserMgt\Http\Controllers\UserMgtController;

Route::middleware('ek-web')->prefix('user-mgt')->group(function () {

    // Landing. The container cannot be perm:-gated (it throws on containers); the
    // controller enforces visibility from the composed menu instead.
    Route::get('/', [UserMgtController::class, 'index'])->name('usermgt.index');
    Route::get('data', [UserMgtController::class, 'dataTable'])->name('usermgt.data')->middleware('perm:user_mgt.users,read');

    // ---- Users (leaf: user_mgt.users) ----
    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
        Route::middleware('perm:user_mgt.users,read')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('data', 'dataTable')->name('data');
            Route::get('{id}', 'show')->name('show')->whereUuid('id');
        });

        Route::middleware('perm:user_mgt.users,read_write')->group(function () {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{id}/edit', 'edit')->name('edit')->whereUuid('id');
            Route::put('{id}', 'update')->name('update')->whereUuid('id');
        });

        Route::middleware('perm:user_mgt.users,full_control')
            ->delete('{id}', 'destroy')->name('destroy')->whereUuid('id');
    });

    // ---- Roles (leaf: user_mgt.roles) ----
    Route::controller(RoleController::class)->prefix('roles')->name('roles.')->group(function () {
        Route::middleware('perm:user_mgt.roles,read')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('data', 'dataTable')->name('data');
            Route::get('{id}', 'show')->name('show')->whereUuid('id');
        });
        Route::middleware('perm:user_mgt.roles,read_write')->group(function () {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{id}/edit', 'edit')->name('edit')->whereUuid('id');
            Route::put('{id}', 'update')->name('update')->whereUuid('id');
        });
        Route::middleware('perm:user_mgt.roles,full_control')
            ->delete('{id}', 'destroy')->name('destroy')->whereUuid('id');
    });

    // ---- Access Controls: the permission matrix (leaf: user_mgt.permissions) ----
    // Not a CRUD resource — a view of one user's matrix and a save. Editing the matrix is
    // a read_write action; there is no create or delete.
    Route::controller(AccessControlController::class)->prefix('access-controls')->name('access-controls.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('perm:user_mgt.permissions,read');
        Route::get('{userId}', 'edit')->name('edit')->whereUuid('userId')->middleware('perm:user_mgt.permissions,read');
        Route::put('{userId}', 'update')->name('update')->whereUuid('userId')->middleware('perm:user_mgt.permissions,read_write');
    });
});