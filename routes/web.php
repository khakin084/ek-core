<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => ''], function () {
    Route::get('/', [AuthController::class, 'index'])->name('login-index');
    Route::post('authenticate', [AuthController::class, 'authenticateUser'])->name('login-auth');
});

Route::group(['prefix' => 'home-page', 'middleware' => 'ek-web'], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home-page');
});

Route::get('logout', [AuthController::class, 'logout'])->name('logout')->middleware('ek-web');

Route::get('/health', function () {
    $checks = [];
    $status = 200;
    $overallStatus = 'healthy';

    // 1. Database Connection Check
    try {
        DB::connection()->getPdo();
        $checks['database'] = [
            'status' => 'healthy',
            'message' => 'Database connection established'
        ];
    } catch (Exception $e) {
        $checks['database'] = [
            'status' => 'unhealthy',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
        $overallStatus = 'unhealthy';
        $status = 503;
    }

    // 2. Cache System Check
    try {
        Cache::put('health-check', 'ok', 10);
        $cacheValue = Cache::get('health-check');

        $checks['cache'] = [
            'status' => $cacheValue === 'ok' ? 'healthy' : 'unhealthy',
            'message' => $cacheValue === 'ok' ? 'Cache system working' : 'Cache retrieval failed'
        ];

        if ($cacheValue !== 'ok') {
            $overallStatus = 'unhealthy';
            $status = 503;
        }
    } catch (Exception $e) {
        $checks['cache'] = [
            'status' => 'unhealthy',
            'message' => 'Cache system failed: ' . $e->getMessage()
        ];
        $overallStatus = 'unhealthy';
        $status = 503;
    }

    // 3. Storage Check
    try {
        Storage::disk()->put('health-check.txt', 'test');
        $fileExists = Storage::disk()->exists('health-check.txt');
        Storage::disk()->delete('health-check.txt');

        $checks['storage'] = [
            'status' => $fileExists ? 'healthy' : 'unhealthy',
            'message' => $fileExists ? 'Storage system working' : 'Storage write/read failed'
        ];

        if (!$fileExists) {
            $overallStatus = 'unhealthy';
            $status = 503;
        }
    } catch (Exception $e) {
        $checks['storage'] = [
            'status' => 'unhealthy',
            'message' => 'Storage system failed: ' . $e->getMessage()
        ];
        $overallStatus = 'unhealthy';
        $status = 503;
    }

    // 4. Memory Usage Check
    $memoryUsage = memory_get_usage(true);
    $memoryLimit = ini_get('memory_limit');
    $checks['memory'] = [
        'status' => 'healthy',
        'message' => "Memory usage: " . round($memoryUsage / 1024 / 1024, 2) . "MB, Memory limit: " . $memoryLimit,
        'usage_bytes' => $memoryUsage
    ];

    return response()->json([
        'status' => $overallStatus,
        'timestamp' => now()->toISOString(),
        'service' => config('app.name', 'Ek Service'),
        'version' => config('app.version', '1.0.0'),
        'checks' => $checks
    ], $status);
});

