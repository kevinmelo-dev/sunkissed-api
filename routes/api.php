<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Src\Admin\Infrastructure\Http\LoginAdminController;
use Src\Catalog\Infrastructure\Http\CreateCategoryController;
use Src\Catalog\Infrastructure\Http\CreateColorController;
use Src\Catalog\Infrastructure\Http\CreateSizeController;
use Src\Catalog\Infrastructure\Http\DeactivateCategoryController;
use Src\Catalog\Infrastructure\Http\DeactivateColorController;
use Src\Catalog\Infrastructure\Http\DeactivateSizeController;
use Src\Catalog\Infrastructure\Http\ListCategoriesController;
use Src\Catalog\Infrastructure\Http\ListColorsController;
use Src\Catalog\Infrastructure\Http\ListSizesController;
use Src\Catalog\Infrastructure\Http\ReactivateCategoryController;
use Src\Catalog\Infrastructure\Http\ReactivateColorController;
use Src\Catalog\Infrastructure\Http\ReactivateSizeController;
use Src\Catalog\Infrastructure\Http\RegisterStockEntryController;
use Src\Catalog\Infrastructure\Http\UpdateCategoryController;
use Src\Catalog\Infrastructure\Http\UpdateColorController;
use Src\Catalog\Infrastructure\Http\UpdateSizeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function (): void {
    Route::prefix('admin')->group(function (): void {
        Route::post('login', LoginAdminController::class);

        Route::middleware('auth:admin')->group(function (): void {
            Route::prefix('catalog')->group(function (): void {
                Route::get('colors', ListColorsController::class);
                Route::post('colors', CreateColorController::class);
                Route::patch('colors/{id}', UpdateColorController::class);
                Route::patch('colors/{id}/deactivate', DeactivateColorController::class);
                Route::patch('colors/{id}/reactivate', ReactivateColorController::class);

                Route::get('sizes', ListSizesController::class);
                Route::post('sizes', CreateSizeController::class);
                Route::patch('sizes/{id}', UpdateSizeController::class);
                Route::patch('sizes/{id}/deactivate', DeactivateSizeController::class);
                Route::patch('sizes/{id}/reactivate', ReactivateSizeController::class);

                Route::get('categories', ListCategoriesController::class);
                Route::post('categories', CreateCategoryController::class);
                Route::patch('categories/{id}', UpdateCategoryController::class);
                Route::patch('categories/{id}/deactivate', DeactivateCategoryController::class);
                Route::patch('categories/{id}/reactivate', ReactivateCategoryController::class);
            });
        });
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::prefix('catalog')->group(function (): void {
            Route::post('stock-entries', RegisterStockEntryController::class);
        });
    });
});
