<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Admin\Application\Port\AdminTokenIssuer;
use Src\Admin\Application\Port\PasswordVerifier;
use Src\Admin\Domain\Repository\AdminRepository;
use Src\Admin\Infrastructure\Auth\LaravelPasswordVerifier;
use Src\Admin\Infrastructure\Auth\SanctumAdminTokenIssuer;
use Src\Admin\Infrastructure\Repository\EloquentAdminRepository;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminRepository::class, EloquentAdminRepository::class);
        $this->app->bind(PasswordVerifier::class, LaravelPasswordVerifier::class);
        $this->app->bind(AdminTokenIssuer::class, SanctumAdminTokenIssuer::class);
    }
}
