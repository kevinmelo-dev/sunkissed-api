<?php

declare(strict_types=1);

use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Application\ProductCoverImage\ProductCoverImageResolver;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Entity\ProductColorImage;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;

function fakeImageRepoForResolver(array $grouped = []): ProductColorImageRepository
{
    return new class($grouped) implements ProductColorImageRepository
    {
        public function __construct(private array $grouped) {}

        public function find(int $id): ?ProductColorImage
        {
            return null;
        }

        public function listForProductColor(int $productId, int $colorId): array
        {
            return $this->grouped[$colorId] ?? [];
        }

        public function listForProduct(int $productId): array
        {
            return $this->grouped;
        }

        public function nextPosition(int $productId, int $colorId): int
        {
            return 1;
        }

        public function save(ProductColorImage $image): ProductColorImage
        {
            return $image;
        }

        public function delete(int $id): void {}

        public function saveOrder(array $orderedIds): void {}
    };
}

function fakeStorageForResolver(): ImageStorage
{
    return new class implements ImageStorage
    {
        public function store(string $tempPath, string $mimeType, string $prefix): string
        {
            return '';
        }

        public function delete(string $storageKey): void {}

        public function url(string $storageKey): string
        {
            return 'https://cdn.example.com/'.$storageKey;
        }
    };
}

function makeProductForResolver(int $id = 1, ?int $coverColorId = null): Product
{
    return new Product(id: $id, type: ProductType::Kit, name: 'P', slug: 'p', description: null, active: true, coverColorId: $coverColorId);
}

function makeImg(int $id, int $productId, int $colorId, string $key, int $position): ProductColorImage
{
    return new ProductColorImage(id: $id, productId: $productId, colorId: $colorId, storageKey: $key, position: $position);
}

it('returns URL from cover_color_id when set and has images', function (): void {
    $grouped = [
        3 => [makeImg(1, 1, 3, 'products/1/colors/3/a.jpg', 1)],
        5 => [makeImg(2, 1, 5, 'products/1/colors/5/b.jpg', 1)],
    ];

    $resolver = new ProductCoverImageResolver(
        fakeImageRepoForResolver($grouped),
        fakeStorageForResolver(),
    );

    $url = $resolver->resolve(makeProductForResolver(coverColorId: 3));

    expect($url)->toBe('https://cdn.example.com/products/1/colors/3/a.jpg');
});

it('falls back to first active color by id when cover_color_id is null', function (): void {
    $grouped = [
        5 => [makeImg(2, 1, 5, 'products/1/colors/5/b.jpg', 1)],
        3 => [makeImg(1, 1, 3, 'products/1/colors/3/a.jpg', 1)],
    ];

    $resolver = new ProductCoverImageResolver(
        fakeImageRepoForResolver($grouped),
        fakeStorageForResolver(),
    );

    $url = $resolver->resolve(makeProductForResolver(coverColorId: null));

    expect($url)->toBe('https://cdn.example.com/products/1/colors/3/a.jpg');
});

it('falls back when cover_color_id is set but that color has no images', function (): void {
    $grouped = [
        3 => [],
        5 => [makeImg(2, 1, 5, 'products/1/colors/5/b.jpg', 1)],
    ];

    $resolver = new ProductCoverImageResolver(
        fakeImageRepoForResolver($grouped),
        fakeStorageForResolver(),
    );

    $url = $resolver->resolve(makeProductForResolver(coverColorId: 3));

    expect($url)->toBe('https://cdn.example.com/products/1/colors/5/b.jpg');
});

it('returns null when product has no images at all', function (): void {
    $resolver = new ProductCoverImageResolver(
        fakeImageRepoForResolver([]),
        fakeStorageForResolver(),
    );

    $url = $resolver->resolve(makeProductForResolver());

    expect($url)->toBeNull();
});
