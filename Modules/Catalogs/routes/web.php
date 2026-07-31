<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalogs\Http\Controllers\CatalogsController;
use Modules\ItemMaster\Http\Controllers\VarietyParticularsController;

Route::prefix('catalogs')->middleware(['ek-web'])->group(function () {
    Route::get('/', [CatalogsController::class, 'index'])->name('catalogs');
    Route::post('list', [CatalogsController::class, 'itemsDataTable'])->name('catalogs-list');
    Route::post('store', [CatalogsController::class, 'storeItem'])->name('item-store');
    Route::post('rearrange-variations', [CatalogsController::class, 'rearrangeVariations'])->name('rearrange-variations')->middleware('audit.bff');
    Route::get('get-uom/{id}', [CatalogsController::class, 'getUom'])->name('get-uom');
    Route::delete('delete/{id}', [CatalogsController::class, 'deleteItem'])->name('item-delete');
    Route::get('create/{id?}', [CatalogsController::class, 'createItem'])->name('item-create');
    

    Route::get('get-item-components/{item_id}', [CatalogsController::class, 'getItemComponents'])->name('get-item-components');
    Route::get('get-item-track-records/{item_id}', [CatalogsController::class, 'getItemTrackRecords'])->name('get-item-track-records');
    Route::get('get-item-variations/{variety_id}/{item_id?}', [CatalogsController::class, 'getVariations'])->name('get-item-variations');




    Route::get('get-item-av-qty/{id}', [CatalogsController::class, 'getItemAvailableQty']);

    // Item Groups
    Route::prefix('item-groups')->group(function () {
        Route::get('create/{id?}', [CatalogsController::class, 'createItemGroup'])->name('item-group-create');
        Route::delete('delete/{id}', [CatalogsController::class, 'deleteItemGroup'])->name('item-group-delete');
        Route::post('store', [CatalogsController::class, 'storeItemGroup'])->name('item-group-store')->middleware('audit.bff');
        Route::get('list', [CatalogsController::class, 'itemGroupsDataTable'])->name('item-groups-list');
    });

    // Varieties
    Route::prefix('varieties')->group(function () {
        Route::delete('delete/{id}', [CatalogsController::class, 'deleteVariety'])->name('variety-delete');
        Route::get('create/{id?}', [CatalogsController::class, 'createVariety'])->name('variety-create');
        Route::post('store', [CatalogsController::class, 'storeVariety'])->name('variety-store')->middleware('audit.bff');
        Route::get('list', [CatalogsController::class, 'varietyDataTable'])->name('varieties-list');
    });

    // Variety Particulars
    Route::prefix('variety-particulars')->group(function () {
        Route::delete('delete/{id}', [CatalogsController::class, 'deleteVarietyParticular']);
        Route::get('create-value/{id?}', [CatalogsController::class, 'createVarietyParticularValue'])->name('variety-particulars.create-value');
        Route::get('create/{id?}', [CatalogsController::class, 'createVarietyParticular'])->name('variety-particulars-create');
        Route::post('store-value', [CatalogsController::class, 'storeVarietyParticularValue'])->name('variety-particulars-store-value');
        Route::post('store', [CatalogsController::class, 'storeVarietyParticular'])->name('variety-particulars-store')->middleware('audit.bff');
        Route::get('list', [CatalogsController::class, 'varietyParticularDataTable']);
    });


    // Units
    Route::prefix('units')->group(function () {
        Route::get('create/{id?}', [CatalogsController::class, 'createUnit'])->name('unit-create');
        Route::post('store', [CatalogsController::class, 'storeUnit'])->name('unit-store')->middleware('audit.bff');
    });

});


// use Modules\ItemMaster\Http\Controllers\ItemMasterController;
// use Modules\ItemMaster\Http\Controllers\UnitsController;

// Route::prefix('item-master')->middleware('ek-api')->group(function () {


//     // Composite Item
//     Route::prefix('composite-item')->group(function () {
//         Route::get('create', [ItemMasterController::class, 'createCompositeItem'])->name('composite-item-create');
//         Route::post('store', [ItemMasterController::class, 'storeCompositeItem'])->name('composite-item-store');
//     });

//     // Other Item Master routes
//     Route::get('{num1}/{num2}/{alpha}/unit-and-last-price', [ItemMasterController::class, 'retrieveUnitNLastPrice'])
//         ->where('alpha', '[A-Za-z]+'); // (:alpha) only alphabetic

//     Route::get('index', [ItemMasterController::class, 'index'])->name('item-master-index');
//     Route::post('list', [ItemMasterController::class, 'itemsDataTable']);
// });