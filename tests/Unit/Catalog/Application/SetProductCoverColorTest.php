<?php

declare(strict_types=1);

use Src\Catalog\Application\SetProductCoverColor\SetProductCoverColor;
use Src\Catalog\Application\SetProductCoverColor\SetProductCoverColorCommand;
use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Exception\ColorInactiveException;
use Src\Catalog\Domain\Exception\ColorNotAssociatedWithProductException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

function fakeProductRepoForCover(array $products = []): ProductRepository
{
    return new class($products) implements ProductRepository
    {
        private array $store = [];

        public function __construct(array $products)
        {
            foreach ($products as $p) {
                $this->store[$p->id()] = $p;
            }
        }

        public function find(int $id): ?Product
        {
            return $this->store[$id] ?? null;
        }

        public function findBySlug(string $slug): ?Product
        {
            return null;
        }

        public function all(bool $onlyActive = false): array
        {
            return array_values($this->store);
        }

        public function existsBySlug(string $slug, ?int $excludeId = null): bool
        {
            return false;
        }

        public function save(Product $product): Product
        {
            $this->store[$product->id()] = $product;

            return $product;
        }
    };
}

function fakeColorRepoForCover(array $colors = []): ColorRepository
{
    return new class($colors) implements ColorRepository
    {
        private array $store = [];

        public function __construct(array $colors)
        {
            foreach ($colors as $c) {
                $this->store[$c->id()] = $c;
            }
        }

        public function find(int $id): ?Color
        {
            return $this->store[$id] ?? null;
        }

        public function all(bool $onlyActive = false): array
        {
            return array_values($this->store);
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

function fakeVariantRepoForCover(bool $hasColor = true): ProductVariantRepository
{
    return new class($hasColor) implements ProductVariantRepository
    {
        public function __construct(private bool $hasColor) {}

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

function fakeAuditForCover(): AuditLogger
{
    return new class implements AuditLogger
    {
        public function log(AuditEvent $e): void {}

        public function batch(AuditBatchContext $c, string $d, int $t, callable $w): AuditBatch
        {
            throw new RuntimeException('not expected');
        }
    };
}

function makeCoverUseCase(
    array $products = [],
    array $colors = [],
    bool $colorAssociated = true,
): SetProductCoverColor {
    return new SetProductCoverColor(
        products: fakeProductRepoForCover($products),
        colors: fakeColorRepoForCover($colors),
        variants: fakeVariantRepoForCover($colorAssociated),
        audit: fakeAuditForCover(),
    );
}

it('sets cover_color_id on the product', function (): void {
    $product = new Product(id: 1, type: ProductType::Kit, name: 'P', slug: 'p', description: null, active: true);
    $color = new Color(id: 2, name: 'Azul', hex: null, active: true);

    $result = makeCoverUseCase([$product], [$color])->execute(
        new SetProductCoverColorCommand(productId: 1, colorId: 2, actorId: 1),
    );

    expect($result->coverColorId())->toBe(2);
});

it('throws ColorInactiveException when color is inactive', function (): void {
    $product = new Product(id: 1, type: ProductType::Kit, name: 'P', slug: 'p', description: null, active: true);
    $color = new Color(id: 2, name: 'Azul', hex: null, active: false);

    makeCoverUseCase([$product], [$color])->execute(
        new SetProductCoverColorCommand(productId: 1, colorId: 2, actorId: 1),
    );
})->throws(ColorInactiveException::class);

it('throws ColorNotAssociatedWithProductException when color not in product variants', function (): void {
    $product = new Product(id: 1, type: ProductType::Kit, name: 'P', slug: 'p', description: null, active: true);
    $color = new Color(id: 2, name: 'Azul', hex: null, active: true);

    makeCoverUseCase([$product], [$color], colorAssociated: false)->execute(
        new SetProductCoverColorCommand(productId: 1, colorId: 2, actorId: 1),
    );
})->throws(ColorNotAssociatedWithProductException::class);
