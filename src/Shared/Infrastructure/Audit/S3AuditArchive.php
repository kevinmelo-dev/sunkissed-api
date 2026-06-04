<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit;

use Illuminate\Contracts\Filesystem\Filesystem;
use Src\Shared\Domain\Audit\AuditArchive;

/**
 * Durable audit archive on an S3-compatible disk (AWS S3 or Cloudflare R2). Events are
 * stored as JSON Lines (one JSON object per line), UTF-8, under a configurable base
 * path.
 *
 * Encoding note: unlike the production logger this replaces, nothing is converted to
 * ISO-8859-1. JSON is encoded with JSON_UNESCAPED_UNICODE so accents and any other
 * UTF-8 content are stored faithfully and the files open correctly in modern tools.
 *
 * Append semantics: object stores do not support true append, so we read-modify-write
 * the line file for the given key. For pointwise events this is a small daily file; for
 * batches the key is unique per run, so there is no contention.
 */
final readonly class S3AuditArchive implements AuditArchive
{
    public function __construct(
        private Filesystem $disk,
        private string $basePath,
    ) {}

    public function append(string $key, array $events): string
    {
        $path = $this->pathFor($key);

        $lines = array_map(
            static fn (array $event): string => json_encode(
                $event,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ),
            $events,
        );

        $newContent = implode(PHP_EOL, $lines).PHP_EOL;

        $existing = $this->disk->exists($path) ? $this->disk->get($path) : '';
        $this->disk->put($path, $existing.$newContent);

        return $path;
    }

    private function pathFor(string $key): string
    {
        return trim($this->basePath, '/')."/{$key}.log";
    }
}
