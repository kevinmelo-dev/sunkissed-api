<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Src\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    SharedServiceProvider::class,
];
