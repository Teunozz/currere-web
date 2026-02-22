<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BatchStoreRunController;
use App\Http\Controllers\Api\V1\IndexRunController;
use App\Http\Controllers\Api\V1\ShowRunController;
use App\Http\Controllers\Api\V1\StoreRunController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::post('/runs', StoreRunController::class);
    Route::post('/runs/batch', BatchStoreRunController::class);
    Route::get('/runs', IndexRunController::class);
    Route::get('/runs/{run}', ShowRunController::class);
});
