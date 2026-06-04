<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Src\Catalog\Infrastructure\Http\RegisterStockEntryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::prefix('catalog')->group(function (): void {
        Route::post('stock-entries', RegisterStockEntryController::class);
    });
});
