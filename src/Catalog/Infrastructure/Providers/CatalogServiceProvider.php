<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\InventoryMovementRepository;
use Src\Catalog\Domain\Repository\ProductCategoryRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Catalog\Infrastructure\Repository\EloquentCategoryRepository;
use Src\Catalog\Infrastructure\Repository\EloquentColorRepository;
use Src\Catalog\Infrastructure\Repository\EloquentInventoryMovementRepository;
use Src\Catalog\Infrastructure\Repository\EloquentProductCategoryRepository;
use Src\Catalog\Infrastructure\Repository\EloquentProductRepository;
use Src\Catalog\Infrastructure\Repository\EloquentProductVariantRepository;
use Src\Catalog\Infrastructure\Repository\EloquentSizeRepository;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(ProductVariantRepository::class, EloquentProductVariantRepository::class);
        $this->app->bind(ProductCategoryRepository::class, EloquentProductCategoryRepository::class);
        $this->app->bind(ColorRepository::class, EloquentColorRepository::class);
        $this->app->bind(SizeRepository::class, EloquentSizeRepository::class);
        $this->app->bind(InventoryMovementRepository::class, EloquentInventoryMovementRepository::class);
        $this->app->bind(CategoryRepository::class, EloquentCategoryRepository::class);
    }
}
