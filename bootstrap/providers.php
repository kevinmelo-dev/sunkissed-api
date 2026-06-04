<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Src\Admin\Infrastructure\Providers\AdminServiceProvider;
use Src\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use Src\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    SharedServiceProvider::class,
    CatalogServiceProvider::class,
    AdminServiceProvider::class,
];
