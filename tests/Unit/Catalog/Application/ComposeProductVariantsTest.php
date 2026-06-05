<?php

declare(strict_types=1);

use Src\Catalog\Application\ComposeProductVariants\ComposeProductVariants;
use Src\Catalog\Application\ComposeProductVariants\ComposeProductVariantsCommand;
use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Exception\SizeNotFoundException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Catalog\Domain\Service\VariantCompositionService;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\ValueObject\Money;

function fakeProductRepoForCompose(array $products = []): ProductRepository
{
    return new class($products) implements ProductRepository
    {
        private array $store = [];

        private int $nextId = 1;

        public function __construct(array $products)
        {
            foreach ($products as $p) {
                $this->store[$p->id() ?? $this->nextId++] = $p;
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

function fakeVariantRepoForCompose(array $variants = []): ProductVariantRepository
{
    return new class($variants) implements ProductVariantRepository
    {
        /** @var ProductVariant[] */
        private array $store = [];

        private int $nextId = 1;

        public function __construct(array $variants)
        {
            foreach ($variants as $v) {
                $id = $v->id() ?? $this->nextId++;
                $this->store[$id] = $v;
                if ($id >= $this->nextId) {
                    $this->nextId = $id + 1;
                }
            }
        }

        public function find(int $id): ?ProductVariant
        {
            return $this->store[$id] ?? null;
        }

        public function findBySku(Sku $sku): ?ProductVariant
        {
            foreach ($this->store as $v) {
                if ($v->sku()->equals($sku)) {
                    return $v;
                }
            }

            return null;
        }

        public function findCombination(int $productId, int $colorId, int $sizeId): ?ProductVariant
        {
            foreach ($this->store as $v) {
                if ($v->productId() === $productId && $v->colorId() === $colorId && $v->sizeId() === $sizeId) {
                    return $v;
                }
            }

            return null;
        }

        public function existsCombination(int $productId, int $colorId, int $sizeId): bool
        {
            return $this->findCombination($productId, $colorId, $sizeId) !== null;
        }

        public function findForProduct(int $productId): array
        {
            return array_values(array_filter(
                $this->store,
                fn (ProductVariant $v) => $v->productId() === $productId,
            ));
        }

        public function save(ProductVariant $variant): ProductVariant
        {
            if ($variant->id() !== null) {
                $this->store[$variant->id()] = $variant;

                return $variant;
            }

            $id = $this->nextId++;
            $saved = new ProductVariant($id, $variant->productId(), $variant->colorId(), $variant->sizeId(), $variant->sku(), $variant->price(), $variant->active(), $variant->image());
            $this->store[$id] = $saved;

            return $saved;
        }
    };
}

function fakeColorRepoForCompose(array $colors = []): ColorRepository
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

function fakeSizeRepoForCompose(array $sizes = []): SizeRepository
{
    return new class($sizes) implements SizeRepository
    {
        private array $store = [];

        public function __construct(array $sizes)
        {
            foreach ($sizes as $s) {
                $this->store[$s->id()] = $s;
            }
        }

        public function find(int $id): ?Size
        {
            return $this->store[$id] ?? null;
        }

        public function all(bool $onlyActive = false): array
        {
            return [];
        }

        public function existsByName(string $name, ?int $excludeId = null): bool
        {
            return false;
        }

        public function save(Size $size): Size
        {
            return $size;
        }
    };
}

function fakeAuditForCompose(): AuditLogger
{
    return new class implements AuditLogger
    {
        public function log(AuditEvent $event): void {}

        public function batch(AuditBatchContext $context, string $description, int $total, callable $work): AuditBatch
        {
            throw new RuntimeException('not expected');
        }
    };
}

function makeComposeUseCase(
    ProductRepository $products,
    ProductVariantRepository $variants,
    ColorRepository $colors,
    SizeRepository $sizes,
): ComposeProductVariants {
    return new ComposeProductVariants(
        $products,
        $variants,
        $colors,
        $sizes,
        new VariantCompositionService,
        fakeAuditForCompose(),
    );
}

function makeProduct(int $id = 1): Product
{
    return new Product($id, ProductType::Kit, 'Produto', 'produto', null, true);
}

function makeColor(int $id, bool $active = true): Color
{
    return new Color($id, "Cor $id", null, $active);
}

function makeSize(int $id, bool $active = true): Size
{
    return new Size($id, "Tam $id", $id, $active);
}

it('generates 6 variants for 2 colors × 3 sizes on empty product', function (): void {
    $product = makeProduct(1);
    $colors = [makeColor(1), makeColor(2)];
    $sizes = [makeSize(1), makeSize(2), makeSize(3)];
    $variantRepo = fakeVariantRepoForCompose();

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        $variantRepo,
        fakeColorRepoForCompose($colors),
        fakeSizeRepoForCompose($sizes),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [1, 2],
        sizeIds: [1, 2, 3],
        actorId: 1,
    ));

    $all = $variantRepo->findForProduct(1);

    expect($all)->toHaveCount(6);

    $skus = array_map(fn (ProductVariant $v) => $v->sku()->value, $all);
    expect(array_unique($skus))->toHaveCount(6);

    foreach ($all as $variant) {
        expect($variant->active())->toBeTrue()
            ->and($variant->price()->cents)->toBe(0);
    }
});

it('adding 1 color creates only 3 new variants and leaves existing 6 intact', function (): void {
    $product = makeProduct(1);
    $colors = [makeColor(1), makeColor(2), makeColor(3)];
    $sizes = [makeSize(1), makeSize(2), makeSize(3)];

    $existingVariants = [];
    foreach ([1, 2] as $colorId) {
        foreach ([1, 2, 3] as $sizeId) {
            $existingVariants[] = new ProductVariant(
                id: count($existingVariants) + 1,
                productId: 1,
                colorId: $colorId,
                sizeId: $sizeId,
                sku: new Sku("P1C{$colorId}S{$sizeId}"),
                price: Money::zero(),
                active: true,
            );
        }
    }

    $variantRepo = fakeVariantRepoForCompose($existingVariants);

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        $variantRepo,
        fakeColorRepoForCompose($colors),
        fakeSizeRepoForCompose($sizes),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [1, 2, 3],
        sizeIds: [1, 2, 3],
        actorId: 1,
    ));

    $all = $variantRepo->findForProduct(1);

    expect($all)->toHaveCount(9);

    $newVariants = array_filter($all, fn (ProductVariant $v) => $v->colorId() === 3);
    expect(array_values($newVariants))->toHaveCount(3);
});

it('removing 1 size deactivates its 2 variants without deleting them', function (): void {
    $product = makeProduct(1);
    $colors = [makeColor(1), makeColor(2)];
    $sizes = [makeSize(1), makeSize(2), makeSize(3)];

    $existingVariants = [];
    foreach ([1, 2] as $colorId) {
        foreach ([1, 2, 3] as $sizeId) {
            $existingVariants[] = new ProductVariant(
                id: count($existingVariants) + 1,
                productId: 1,
                colorId: $colorId,
                sizeId: $sizeId,
                sku: new Sku("P1C{$colorId}S{$sizeId}"),
                price: Money::zero(),
                active: true,
            );
        }
    }

    $variantRepo = fakeVariantRepoForCompose($existingVariants);

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        $variantRepo,
        fakeColorRepoForCompose($colors),
        fakeSizeRepoForCompose($sizes),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [1, 2],
        sizeIds: [1, 2],
        actorId: 1,
    ));

    $all = $variantRepo->findForProduct(1);

    expect($all)->toHaveCount(6);

    $deactivated = array_filter($all, fn (ProductVariant $v) => $v->sizeId() === 3);
    foreach ($deactivated as $v) {
        expect($v->active())->toBeFalse();
    }

    $active = array_filter($all, fn (ProductVariant $v) => $v->sizeId() !== 3);
    foreach ($active as $v) {
        expect($v->active())->toBeTrue();
    }
});

it('reselecting a removed size reactivates variants without duplicates', function (): void {
    $product = makeProduct(1);
    $colors = [makeColor(1), makeColor(2)];
    $sizes = [makeSize(1), makeSize(2), makeSize(3)];

    $existingVariants = [];
    foreach ([1, 2] as $colorId) {
        foreach ([1, 2] as $sizeId) {
            $existingVariants[] = new ProductVariant(
                id: count($existingVariants) + 1,
                productId: 1,
                colorId: $colorId,
                sizeId: $sizeId,
                sku: new Sku("P1C{$colorId}S{$sizeId}"),
                price: Money::fromCents(5000),
                active: $sizeId !== 2,
            );
        }
    }

    $variantRepo = fakeVariantRepoForCompose($existingVariants);

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        $variantRepo,
        fakeColorRepoForCompose($colors),
        fakeSizeRepoForCompose($sizes),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [1, 2],
        sizeIds: [1, 2],
        actorId: 1,
    ));

    $all = $variantRepo->findForProduct(1);

    expect($all)->toHaveCount(4);

    $reactivated = array_filter($all, fn (ProductVariant $v) => $v->sizeId() === 2);
    foreach ($reactivated as $v) {
        expect($v->active())->toBeTrue();
    }

    expect($variantRepo->findForProduct(1))->toHaveCount(4);
});

it('throws ColorNotFoundException for an unknown color_id', function (): void {
    $product = makeProduct(1);

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        fakeVariantRepoForCompose(),
        fakeColorRepoForCompose([makeColor(1)]),
        fakeSizeRepoForCompose([makeSize(1)]),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [99],
        sizeIds: [1],
        actorId: 1,
    ));
})->throws(ColorNotFoundException::class);

it('throws ColorNotFoundException for an inactive color', function (): void {
    $product = makeProduct(1);

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        fakeVariantRepoForCompose(),
        fakeColorRepoForCompose([makeColor(1, active: false)]),
        fakeSizeRepoForCompose([makeSize(1)]),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [1],
        sizeIds: [1],
        actorId: 1,
    ));
})->throws(ColorNotFoundException::class);

it('throws SizeNotFoundException for an unknown size_id', function (): void {
    $product = makeProduct(1);

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        fakeVariantRepoForCompose(),
        fakeColorRepoForCompose([makeColor(1)]),
        fakeSizeRepoForCompose([makeSize(1)]),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [1],
        sizeIds: [99],
        actorId: 1,
    ));
})->throws(SizeNotFoundException::class);

it('throws SizeNotFoundException for an inactive size', function (): void {
    $product = makeProduct(1);

    $useCase = makeComposeUseCase(
        fakeProductRepoForCompose([$product]),
        fakeVariantRepoForCompose(),
        fakeColorRepoForCompose([makeColor(1)]),
        fakeSizeRepoForCompose([makeSize(1, active: false)]),
    );

    $useCase->execute(new ComposeProductVariantsCommand(
        productId: 1,
        colorIds: [1],
        sizeIds: [1],
        actorId: 1,
    ));
})->throws(SizeNotFoundException::class);
