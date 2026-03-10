<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BatchStoreRunController;
use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('/ping', PingController::class);
    Route::post('/runs/batch', BatchStoreRunController::class);
});
