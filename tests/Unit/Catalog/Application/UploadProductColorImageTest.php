<?php

declare(strict_types=1);

use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Application\UploadProductColorImage\UploadProductColorImage;
use Src\Catalog\Application\UploadProductColorImage\UploadProductColorImageCommand;
use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Entity\ProductColorImage;
use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Exception\ColorInactiveException;
use Src\Catalog\Domain\Exception\ColorNotAssociatedWithProductException;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\ProductColorImageRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

function fakeImageStorage(string $returnKey = 'products/1/colors/1/abc123.jpg'): ImageStorage
{
    return new class($returnKey) implements ImageStorage
    {
        public array $stored = [];

        public array $deleted = [];

        public function __construct(private string $key) {}

        public function store(string $tempPath, string $mimeType, string $prefix): string
        {
            $this->stored[] = compact('tempPath', 'mimeType', 'prefix');

            return $this->key;
        }

        public function delete(string $storageKey): void
        {
            $this->deleted[] = $storageKey;
        }

        public function url(string $storageKey): string
        {
            return 'https://cdn.example.com/'.$storageKey;
        }
    };
}

function fakeProductRepoForUpload(?Product $product = null): ProductRepository
{
    return new class($product) implements ProductRepository
    {
        public function __construct(private readonly ?Product $product) {}

        public function find(int $id): ?Product
        {
            return $this->product?->id() === $id ? $this->product : null;
        }

        public function findBySlug(string $slug): ?Product
        {
            return null;
        }

        public function all(bool $onlyActive = false): array
        {
            return [];
        }

        public function existsBySlug(string $slug, ?int $excludeId = null): bool
        {
            return false;
        }

        public function save(Product $product): Product
        {
            return $product;
        }
    };
}

function fakeColorRepoForUpload(?Color $color = null): ColorRepository
{
    return new class($color) implements ColorRepository
    {
        public function __construct(private readonly ?Color $color) {}

        public function find(int $id): ?Color
        {
            return $this->color?->id() === $id ? $this->color : null;
        }

        public function all(bool $onlyActive = false): array
        {
            return [];
        }

        public function existsByName(string $name, ?int $excludeId = null): bool
        {
            return false;
        }

        public function save(Color $color): Color
        {
            return $color;
        }
    };
}

function fakeVariantRepoForUpload(bool $hasColor = true): ProductVariantRepository
{
    return new class($hasColor) implements ProductVariantRepository
    {
        public function __construct(private readonly bool $hasColor) {}

        public function find(int $id): ?ProductVariant
        {
            return null;
        }

        public function findBySku(Sku $sku): ?ProductVariant
        {
            return null;
        }

        public function findCombination(int $productId, int $colorId, int $sizeId): ?ProductVariant
        {
            return null;
        }

        public function existsCombination(int $productId, int $colorId, int $sizeId): bool
        {
            return false;
        }

        public function findForProduct(int $productId): array
        {
            return [];
        }

        public function findActiveForProductColor(int $productId, int $colorId): array
        {
            return [];
        }

        public function existsColorForProduct(int $productId, int $colorId): bool
        {
            return $this->hasColor;
        }

        public function save(ProductVariant $variant): ProductVariant
        {
            return $variant;
        }
    };
}

function fakeImageRepo(int $nextPosition = 1): ProductColorImageRepository
{
    return new class($nextPosition) implements ProductColorImageRepository
    {
        private int $nextId = 1;

        public function __construct(private int $position) {}

        public function find(int $id): ?ProductColorImage
        {
            return null;
        }

        public function listForProductColor(int $productId, int $colorId): array
        {
            return [];
        }

        public function listForProduct(int $productId): array
        {
            return [];
        }

        public function nextPosition(int $productId, int $colorId): int
        {
            return $this->position;
        }

        public function save(ProductColorImage $image): ProductColorImage
        {
            return new ProductColorImage(
                id: $this->nextId++,
                productId: $image->productId(),
                colorId: $image->colorId(),
                storageKey: $image->storageKey(),
                position: $image->position(),
            );
        }

        public function delete(int $id): void {}

        public function saveOrder(array $orderedIds): void {}
    };
}

function fakeAuditForImages(): AuditLogger
{
    return new class implements AuditLogger
    {
        public array $logged = [];

        public function log(AuditEvent $event): void
        {
            $this->logged[] = $event;
        }

        public function batch(AuditBatchContext $ctx, string $desc, int $total, callable $work): AuditBatch
        {
            throw new RuntimeException('not expected');
        }
    };
}

function makeProductForUpload(int $id = 1): Product
{
    return new Product(id: $id, type: ProductType::Kit, name: 'Biquíni', slug: 'biquini', description: null, active: true);
}

function makeColorForUpload(int $id = 1, bool $active = true): Color
{
    return new Color(id: $id, name: 'Rosa', hex: null, active: $active);
}

function makeUploadUseCase(
    ?Product $product = null,
    ?Color $color = null,
    bool $colorAssociated = true,
    ?ImageStorage $storage = null,
    int $nextPosition = 1,
): UploadProductColorImage {
    return new UploadProductColorImage(
        products: fakeProductRepoForUpload($product ?? makeProductForUpload()),
        colors: fakeColorRepoForUpload($color ?? makeColorForUpload()),
        variants: fakeVariantRepoForUpload($colorAssociated),
        images: fakeImageRepo($nextPosition),
        storage: $storage ?? fakeImageStorage(),
        audit: fakeAuditForImages(),
    );
}

it('stores the file and persists with position', function (): void {
    $storage = fakeImageStorage('products/1/colors/1/uuid.jpg');

    $image = makeUploadUseCase(storage: $storage)->execute(
        new UploadProductColorImageCommand(
            productId: 1,
            colorId: 1,
            fileTempPath: '/tmp/test.jpg',
            mimeType: 'image/jpeg',
            actorId: 1,
        ),
    );

    expect($image->storageKey())->toBe('products/1/colors/1/uuid.jpg')
        ->and($image->position())->toBe(1)
        ->and($image->productId())->toBe(1)
        ->and($image->colorId())->toBe(1);
});

it('assigns incremental position', function (): void {
    $image = makeUploadUseCase(nextPosition: 3)->execute(
        new UploadProductColorImageCommand(
            productId: 1, colorId: 1, fileTempPath: '/tmp/a.png',
            mimeType: 'image/png', actorId: 1,
        ),
    );

    expect($image->position())->toBe(3);
});

it('throws ProductNotFoundException for unknown product', function (): void {
    $uc = new UploadProductColorImage(
        products: fakeProductRepoForUpload(null),
        colors: fakeColorRepoForUpload(makeColorForUpload()),
        variants: fakeVariantRepoForUpload(),
        images: fakeImageRepo(),
        storage: fakeImageStorage(),
        audit: fakeAuditForImages(),
    );

    $uc->execute(new UploadProductColorImageCommand(1, 1, '/tmp/x.jpg', 'image/jpeg', 1));
})->throws(ProductNotFoundException::class);

it('throws ColorNotFoundException for unknown color', function (): void {
    $uc = new UploadProductColorImage(
        products: fakeProductRepoForUpload(makeProductForUpload()),
        colors: fakeColorRepoForUpload(null),
        variants: fakeVariantRepoForUpload(),
        images: fakeImageRepo(),
        storage: fakeImageStorage(),
        audit: fakeAuditForImages(),
    );

    $uc->execute(new UploadProductColorImageCommand(1, 99, '/tmp/x.jpg', 'image/jpeg', 1));
})->throws(ColorNotFoundException::class);

it('throws ColorInactiveException for inactive color', function (): void {
    $uc = makeUploadUseCase(color: makeColorForUpload(1, false));

    $uc->execute(new UploadProductColorImageCommand(1, 1, '/tmp/x.jpg', 'image/jpeg', 1));
})->throws(ColorInactiveException::class);

it('throws ColorNotAssociatedWithProductException when color has no variants', function (): void {
    $uc = makeUploadUseCase(colorAssociated: false);

    $uc->execute(new UploadProductColorImageCommand(1, 1, '/tmp/x.jpg', 'image/jpeg', 1));
})->throws(ColorNotAssociatedWithProductException::class);
