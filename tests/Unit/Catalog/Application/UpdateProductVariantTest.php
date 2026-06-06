<?php

declare(strict_types=1);

use Src\Catalog\Application\UpdateProductVariant\UpdateProductVariant;
use Src\Catalog\Application\UpdateProductVariant\UpdateProductVariantCommand;
use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Exception\DuplicateSkuException;
use Src\Catalog\Domain\Exception\ProductVariantNotFoundException;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\ValueObject\Money;

function fakeVariantRepoForUpdate(array $variants = []): ProductVariantRepository
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
            return false;
        }

        public function save(ProductVariant $variant): ProductVariant
        {
            $this->store[$variant->id()] = $variant;

            return $variant;
        }
    };
}

function fakeAuditForVariant(): AuditLogger
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

function makeVariantForUpdate(int $id, string $sku = 'P1C1S1', int $priceCents = 0): ProductVariant
{
    return new ProductVariant(id: $id, productId: 1, colorId: 1, sizeId: 1, sku: new Sku($sku), price: Money::fromCents($priceCents), active: true);
}

it('updates price in cents', function (): void {
    $variant = makeVariantForUpdate(1, 'P1C1S1', 0);
    $repo = fakeVariantRepoForUpdate([$variant]);

    $result = (new UpdateProductVariant($repo, fakeAuditForVariant()))->execute(
        new UpdateProductVariantCommand(id: 1, priceCents: 9900, sku: null, actorId: 1),
    );

    expect($result->price()->cents)->toBe(9900);
});

it('updates sku to a new unique value', function (): void {
    $variant = makeVariantForUpdate(1, 'P1C1S1');
    $repo = fakeVariantRepoForUpdate([$variant]);

    $result = (new UpdateProductVariant($repo, fakeAuditForVariant()))->execute(
        new UpdateProductVariantCommand(id: 1, priceCents: null, sku: 'CUSTOM-SKU', actorId: 1),
    );

    expect($result->sku()->value)->toBe('CUSTOM-SKU');
});

it('throws DuplicateSkuException when new sku conflicts with existing variant', function (): void {
    $v1 = makeVariantForUpdate(1, 'P1C1S1');
    $v2 = makeVariantForUpdate(2, 'P1C2S1');
    $repo = fakeVariantRepoForUpdate([$v1, $v2]);

    (new UpdateProductVariant($repo, fakeAuditForVariant()))->execute(
        new UpdateProductVariantCommand(id: 1, priceCents: null, sku: 'P1C2S1', actorId: 1),
    );
})->throws(DuplicateSkuException::class);

it('allows setting sku to the same value (no conflict)', function (): void {
    $variant = makeVariantForUpdate(1, 'P1C1S1');
    $repo = fakeVariantRepoForUpdate([$variant]);

    $result = (new UpdateProductVariant($repo, fakeAuditForVariant()))->execute(
        new UpdateProductVariantCommand(id: 1, priceCents: null, sku: 'P1C1S1', actorId: 1),
    );

    expect($result->sku()->value)->toBe('P1C1S1');
});

it('throws ProductVariantNotFoundException for unknown variant', function (): void {
    $repo = fakeVariantRepoForUpdate();

    (new UpdateProductVariant($repo, fakeAuditForVariant()))->execute(
        new UpdateProductVariantCommand(id: 99, priceCents: null, sku: null, actorId: 1),
    );
})->throws(ProductVariantNotFoundException::class);
