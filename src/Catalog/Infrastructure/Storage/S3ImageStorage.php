<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Src\Catalog\Application\ImageStorage\ImageStorage;

/**
 * Stores product images on an S3-compatible disk (AWS S3 or Cloudflare R2).
 *
 * Required .env keys:
 *   AWS_ACCESS_KEY_ID     — R2/S3 access key
 *   AWS_SECRET_ACCESS_KEY — R2/S3 secret key
 *   AWS_DEFAULT_REGION    — e.g. "auto" for R2, "us-east-1" for S3
 *   AWS_BUCKET            — bucket name
 *   AWS_URL               — public bucket base URL (e.g. https://pub.r2.dev/bucket)
 *   AWS_ENDPOINT          — R2 endpoint: https://<account>.r2.cloudflarestorage.com
 *   AWS_USE_PATH_STYLE_ENDPOINT=true  — required for R2
 *
 * The bucket must be publicly readable so that url() returns accessible links.
 * If the bucket is private, replace url() with a signed-URL approach later.
 */
final class S3ImageStorage implements ImageStorage
{
    private static array $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly Filesystem $disk,
    ) {}

    public function store(string $tempPath, string $mimeType, string $prefix): string
    {
        $ext = self::$mimeToExt[$mimeType] ?? 'jpg';
        $key = "{$prefix}/".str_replace('-', '', (string) Str::uuid()).'.'.$ext;

        $this->disk->put($key, file_get_contents($tempPath));

        return $key;
    }

    public function delete(string $storageKey): void
    {
        $this->disk->delete($storageKey);
    }

    public function url(string $storageKey): string
    {
        return $this->disk->url($storageKey);
    }
}
