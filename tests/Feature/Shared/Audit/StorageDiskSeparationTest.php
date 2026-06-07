<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Shared\Domain\Audit\AuditArchive;

beforeEach(function (): void {
    Storage::fake('images');
    Storage::fake('audit');
});

it('image storage writes to the images disk and not the audit disk', function (): void {
    $tmp = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($tmp, 'fake-image-content');

    /** @var ImageStorage $storage */
    $storage = $this->app->make(ImageStorage::class);
    $key = $storage->store($tmp, 'image/jpeg', 'products/1/colors/1');

    unlink($tmp);

    Storage::disk('images')->assertExists($key);
    Storage::disk('audit')->assertMissing($key);
});

it('audit archive writes to the audit disk and not the images disk', function (): void {
    /** @var AuditArchive $archive */
    $archive = $this->app->make(AuditArchive::class);
    $path = $archive->append('daily/2026-06-06', [['action' => 'test.event', 'actor' => 'system']]);

    Storage::disk('audit')->assertExists($path);
    Storage::disk('images')->assertMissing($path);
});
