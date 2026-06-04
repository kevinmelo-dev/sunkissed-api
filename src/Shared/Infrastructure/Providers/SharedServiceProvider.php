<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Src\Shared\Domain\Audit\AuditArchive;
use Src\Shared\Domain\Audit\AuditBatchRepository;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\Audit\AuditLogRepository;
use Src\Shared\Infrastructure\Audit\DefaultAuditLogger;
use Src\Shared\Infrastructure\Audit\Eloquent\EloquentAuditBatchRepository;
use Src\Shared\Infrastructure\Audit\Eloquent\EloquentAuditLogRepository;
use Src\Shared\Infrastructure\Audit\S3AuditArchive;

/**
 * Wires the Shared context: binds domain audit interfaces to their infrastructure
 * implementations. Each bounded context gets a provider like this; register them in
 * bootstrap/providers.php.
 */
final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogRepository::class, EloquentAuditLogRepository::class);
        $this->app->bind(AuditBatchRepository::class, EloquentAuditBatchRepository::class);

        $this->app->singleton(AuditArchive::class, function (Application $app): AuditArchive {
            $disk = Storage::disk(config('audit.archive_disk'));

            return new S3AuditArchive($disk, (string) config('audit.archive_path'));
        });

        $this->app->bind(AuditLogger::class, DefaultAuditLogger::class);
    }
}
