<?php

declare(strict_types=1);

use Src\Catalog\Application\RegisterStockEntry\RegisterStockEntry;
use Src\Catalog\Application\RegisterStockEntry\RegisterStockEntryCommand;
use Src\Catalog\Domain\Entity\InventoryMovement;
use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Catalog\Domain\Enum\MovementType;
use Src\Catalog\Domain\Exception\VariantNotFoundException;
use Src\Catalog\Domain\Repository\InventoryMovementRepository;
use Src\Catalog\Domain\Repository\ProductVariantRepository;
use Src\Catalog\Domain\Service\StockLedger;
use Src\Catalog\Domain\ValueObject\Sku;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\Exception\DomainException;
use Src\Shared\Domain\ValueObject\Money;

function fakeVariantRepo(?ProductVariant $variant = null): ProductVariantRepository
{
    return new class($variant) implements ProductVariantRepository
    {
        public function __construct(private readonly ?ProductVariant $variant) {}

        public function find(int $id): ?ProductVariant
        {
            return $this->variant?->id() === $id ? $this->variant : null;
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

        public function save(ProductVariant $variant): ProductVariant
        {
            return $variant;
        }
    };
}

function fakeInventoryRepo(): InventoryMovementRepository
{
    return new class implements InventoryMovementRepository
    {
        /** @var InventoryMovement[] */
        public array $movements = [];

        private int $nextId = 1;

        public function append(InventoryMovement $movement): InventoryMovement
        {
            $persisted = $movement->withId($this->nextId++);
            $this->movements[] = $persisted;

            return $persisted;
        }

        public function find(int $id): ?InventoryMovement
        {
            foreach ($this->movements as $m) {
                if ($m->id() === $id) {
                    return $m;
                }
            }

            return null;
        }

        public function movementsForVariant(int $variantId): array
        {
            return array_values(array_filter(
                $this->movements,
                fn (InventoryMovement $m) => $m->variantId() === $variantId,
            ));
        }

        public function activeReservation(int $reservationId): ?InventoryMovement
        {
            return null;
        }

        public function expiredActiveReservations(DateTimeImmutable $now): array
        {
            return [];
        }
    };
}

function fakeAuditLogger(): AuditLogger
{
    return new class implements AuditLogger
    {
        /** @var AuditEvent[] */
        public array $logged = [];

        public function log(AuditEvent $event): void
        {
            $this->logged[] = $event;
        }

        public function batch(
            AuditBatchContext $context,
            string $description,
            int $total,
            callable $work,
        ): AuditBatch {
            throw new RuntimeException('batch not expected in this test');
        }
    };
}

function makeVariant(int $id): ProductVariant
{
    return new ProductVariant(
        id: $id,
        productId: 1,
        colorId: 1,
        sizeId: 1,
        sku: new Sku('SKU-001'),
        price: Money::fromCents(9900),
        active: true,
    );
}

it('happy path: creates one entrada movement and emits one audit event', function (): void {
    $movRepo = fakeInventoryRepo();
    $audit = fakeAuditLogger();
    $varRepo = fakeVariantRepo(makeVariant(42));

    $useCase = new RegisterStockEntry($varRepo, new StockLedger($movRepo), $audit);

    $result = $useCase->execute(new RegisterStockEntryCommand(
        variantId: 42,
        quantity: 10,
        reason: 'initial stock',
        actorId: 1,
    ));

    expect($result->movementId)->toBe(1)
        ->and($result->availableAfter)->toBe(10)
        ->and($movRepo->movements)->toHaveCount(1)
        ->and($movRepo->movements[0]->type())->toBe(MovementType::Entrada)
        ->and($audit->logged)->toHaveCount(1)
        ->and($audit->logged[0]->action)->toBe('stock.entry_registered')
        ->and($audit->logged[0]->subject)->toBe('variant:42');
});

it('throws VariantNotFoundException without touching the ledger when variant does not exist', function (): void {
    $movRepo = fakeInventoryRepo();
    $audit = fakeAuditLogger();
    $varRepo = fakeVariantRepo(null);

    $useCase = new RegisterStockEntry($varRepo, new StockLedger($movRepo), $audit);

    $useCase->execute(new RegisterStockEntryCommand(
        variantId: 99,
        quantity: 5,
        reason: null,
        actorId: 1,
    ));
})->throws(VariantNotFoundException::class)
    ->and(fn () => expect(fakeInventoryRepo()->movements)->toBeEmpty());

it('throws when quantity is zero', function (): void {
    $movRepo = fakeInventoryRepo();
    $varRepo = fakeVariantRepo(makeVariant(1));

    $useCase = new RegisterStockEntry($varRepo, new StockLedger($movRepo), fakeAuditLogger());

    $useCase->execute(new RegisterStockEntryCommand(
        variantId: 1,
        quantity: 0,
        reason: null,
        actorId: 1,
    ));
})->throws(DomainException::class);

it('throws when quantity is negative', function (): void {
    $movRepo = fakeInventoryRepo();
    $varRepo = fakeVariantRepo(makeVariant(1));

    $useCase = new RegisterStockEntry($varRepo, new StockLedger($movRepo), fakeAuditLogger());

    $useCase->execute(new RegisterStockEntryCommand(
        variantId: 1,
        quantity: -5,
        reason: null,
        actorId: 1,
    ));
})->throws(DomainException::class);
