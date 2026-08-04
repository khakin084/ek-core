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
            Route::post('/', 'store')->name('store')->middleware('audit.bff');
            Route::get('{id}/edit', 'edit')->name('edit')->whereUuid('id');
            Route::put('{id}', 'update')->name('update')->whereUuid('id')->middleware('audit.bff');

            Route::prefix('{id}/access')->group(function () {
                Route::get('roles', 'loadRoles')->name('access.roles')->whereUuid('id');
                Route::get('permissions', 'loadPermissions')->name('access.permissions')->whereUuid('id');
                Route::post('save', 'save')->name('access.save')->whereUuid('id')->middleware('audit.bff');
            });
        });

        Route::middleware('perm:user_mgt.users,full_control')
            ->delete('{id}', 'destroy')->name('destroy')->whereUuid('id')->middleware('audit.bff');
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
            Route::post('/', 'store')->name('store')->middleware('audit.bff');
            Route::get('{id}/edit', 'edit')->name('edit')->whereUuid('id');

            Route::prefix('{id}/access')->group(function () {
                Route::get('permissions', 'loadPermissions')->name('access.permissions')->whereUuid('id');
                Route::post('permissions', 'savePermissions')->name('access.permissions.save')->whereUuid('id')->middleware('audit.bff');
            });
        });
        Route::middleware('perm:user_mgt.roles,full_control')
            ->delete('{id}', 'destroy')->name('destroy')->whereUuid('id')->middleware('audit.bff');

    });

});