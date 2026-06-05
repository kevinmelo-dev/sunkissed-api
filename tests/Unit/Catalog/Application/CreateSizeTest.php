<?php

declare(strict_types=1);

use Src\Catalog\Application\CreateSize\CreateSize;
use Src\Catalog\Application\CreateSize\CreateSizeCommand;
use Src\Catalog\Application\DeactivateSize\DeactivateSize;
use Src\Catalog\Application\DeactivateSize\DeactivateSizeCommand;
use Src\Catalog\Application\ListSizes\ListSizes;
use Src\Catalog\Application\ListSizes\ListSizesQuery;
use Src\Catalog\Application\ReactivateSize\ReactivateSize;
use Src\Catalog\Application\ReactivateSize\ReactivateSizeCommand;
use Src\Catalog\Application\UpdateSize\UpdateSize;
use Src\Catalog\Application\UpdateSize\UpdateSizeCommand;
use Src\Catalog\Domain\Entity\Size;
use Src\Catalog\Domain\Exception\DuplicateSizeNameException;
use Src\Catalog\Domain\Exception\SizeNotFoundException;
use Src\Catalog\Domain\Repository\SizeRepository;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

function fakeSizeRepo(array $sizes = []): SizeRepository
{
    return new class($sizes) implements SizeRepository
    {
        /** @var Size[] */
        private array $store = [];

        private int $nextId = 1;

        public function __construct(array $sizes)
        {
            foreach ($sizes as $s) {
                $this->store[$s->id() ?? $this->nextId++] = $s;
            }
        }

        public function find(int $id): ?Size
        {
            return $this->store[$id] ?? null;
        }

        public function all(bool $onlyActive = false): array
        {
            $items = array_values(array_filter(
                $this->store,
                fn (Size $s) => ! $onlyActive || $s->active(),
            ));
            usort($items, fn (Size $a, Size $b) => $a->sortOrder() <=> $b->sortOrder());

            return $items;
        }

        public function existsByName(string $name, ?int $excludeId = null): bool
        {
            foreach ($this->store as $id => $s) {
                if ($s->name() === $name && $id !== $excludeId) {
                    return true;
                }
            }

            return false;
        }

        public function save(Size $size): Size
        {
            $id = $size->id() ?? $this->nextId++;
            $saved = new Size($id, $size->name(), $size->sortOrder(), $size->active());
            $this->store[$id] = $saved;

            return $saved;
        }
    };
}

function fakeAuditLoggerForSize(): AuditLogger
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

it('creates a size and returns it with an id', function (): void {
    $repo = fakeSizeRepo();
    $size = (new CreateSize($repo, fakeAuditLoggerForSize()))->execute(
        new CreateSizeCommand(name: 'P', sortOrder: 1, actorId: 1),
    );

    expect($size->id())->not->toBeNull()
        ->and($size->name())->toBe('P')
        ->and($size->sortOrder())->toBe(1)
        ->and($size->active())->toBeTrue();
});

it('throws DuplicateSizeNameException when name already exists', function (): void {
    $existing = new Size(1, 'P', 1, true);
    $repo = fakeSizeRepo([$existing]);

    (new CreateSize($repo, fakeAuditLoggerForSize()))->execute(
        new CreateSizeCommand(name: 'P', sortOrder: 2, actorId: 1),
    );
})->throws(DuplicateSizeNameException::class);

it('updates name and sort_order', function (): void {
    $existing = new Size(1, 'P', 1, true);
    $repo = fakeSizeRepo([$existing]);

    $updated = (new UpdateSize($repo, fakeAuditLoggerForSize()))->execute(
        new UpdateSizeCommand(id: 1, name: 'PP', sortOrder: 0, actorId: 1),
    );

    expect($updated->name())->toBe('PP')
        ->and($updated->sortOrder())->toBe(0);
});

it('throws SizeNotFoundException when updating non-existent size', function (): void {
    $repo = fakeSizeRepo();

    (new UpdateSize($repo, fakeAuditLoggerForSize()))->execute(
        new UpdateSizeCommand(id: 99, name: 'X', sortOrder: 0, actorId: 1),
    );
})->throws(SizeNotFoundException::class);

it('deactivates a size', function (): void {
    $existing = new Size(1, 'M', 2, true);
    $repo = fakeSizeRepo([$existing]);

    $result = (new DeactivateSize($repo, fakeAuditLoggerForSize()))->execute(
        new DeactivateSizeCommand(id: 1, actorId: 1),
    );

    expect($result->active())->toBeFalse();
});

it('reactivates a size', function (): void {
    $existing = new Size(1, 'M', 2, false);
    $repo = fakeSizeRepo([$existing]);

    $result = (new ReactivateSize($repo, fakeAuditLoggerForSize()))->execute(
        new ReactivateSizeCommand(id: 1, actorId: 1),
    );

    expect($result->active())->toBeTrue();
});

it('ListSizes returns sizes ordered by sort_order', function (): void {
    $repo = fakeSizeRepo([
        new Size(1, 'GG', 4, true),
        new Size(2, 'P', 1, true),
        new Size(3, 'M', 2, true),
        new Size(4, 'G', 3, true),
    ]);

    $results = (new ListSizes($repo))->execute(new ListSizesQuery);

    expect(array_map(fn (Size $s) => $s->name(), $results))
        ->toBe(['P', 'M', 'G', 'GG']);
});
