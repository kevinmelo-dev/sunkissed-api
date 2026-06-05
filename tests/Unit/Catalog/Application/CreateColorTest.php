<?php

declare(strict_types=1);

use Src\Catalog\Application\CreateColor\CreateColor;
use Src\Catalog\Application\CreateColor\CreateColorCommand;
use Src\Catalog\Application\DeactivateColor\DeactivateColor;
use Src\Catalog\Application\DeactivateColor\DeactivateColorCommand;
use Src\Catalog\Application\ListColors\ListColors;
use Src\Catalog\Application\ListColors\ListColorsQuery;
use Src\Catalog\Application\ReactivateColor\ReactivateColor;
use Src\Catalog\Application\ReactivateColor\ReactivateColorCommand;
use Src\Catalog\Application\UpdateColor\UpdateColor;
use Src\Catalog\Application\UpdateColor\UpdateColorCommand;
use Src\Catalog\Domain\Entity\Color;
use Src\Catalog\Domain\Exception\ColorNotFoundException;
use Src\Catalog\Domain\Exception\DuplicateColorNameException;
use Src\Catalog\Domain\Repository\ColorRepository;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

function fakeColorRepo(array $colors = []): ColorRepository
{
    return new class($colors) implements ColorRepository
    {
        /** @var Color[] */
        private array $store = [];

        private int $nextId = 1;

        public function __construct(array $colors)
        {
            foreach ($colors as $c) {
                $this->store[$c->id() ?? $this->nextId++] = $c;
            }
        }

        public function find(int $id): ?Color
        {
            return $this->store[$id] ?? null;
        }

        public function all(bool $onlyActive = false): array
        {
            return array_values(array_filter(
                $this->store,
                fn (Color $c) => ! $onlyActive || $c->active(),
            ));
        }

        public function existsByName(string $name, ?int $excludeId = null): bool
        {
            foreach ($this->store as $id => $c) {
                if ($c->name() === $name && $id !== $excludeId) {
                    return true;
                }
            }

            return false;
        }

        public function save(Color $color): Color
        {
            $id = $color->id() ?? $this->nextId++;
            $saved = new Color($id, $color->name(), $color->hex(), $color->active());
            $this->store[$id] = $saved;

            return $saved;
        }
    };
}

function fakeAuditLoggerForColor(): AuditLogger
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

it('creates a color and returns it with an id', function (): void {
    $repo = fakeColorRepo();
    $color = (new CreateColor($repo, fakeAuditLoggerForColor()))->execute(
        new CreateColorCommand(name: 'Azul', hex: '#0000FF', actorId: 1),
    );

    expect($color->id())->not->toBeNull()
        ->and($color->name())->toBe('Azul')
        ->and($color->hex())->toBe('#0000FF')
        ->and($color->active())->toBeTrue();
});

it('creates a color without hex', function (): void {
    $repo = fakeColorRepo();
    $color = (new CreateColor($repo, fakeAuditLoggerForColor()))->execute(
        new CreateColorCommand(name: 'Branco', hex: null, actorId: 1),
    );

    expect($color->hex())->toBeNull();
});

it('throws DuplicateColorNameException when name already exists', function (): void {
    $existing = new Color(1, 'Azul', '#0000FF', true);
    $repo = fakeColorRepo([$existing]);

    (new CreateColor($repo, fakeAuditLoggerForColor()))->execute(
        new CreateColorCommand(name: 'Azul', hex: null, actorId: 1),
    );
})->throws(DuplicateColorNameException::class);

it('updates name and hex', function (): void {
    $existing = new Color(1, 'Azul', '#0000FF', true);
    $repo = fakeColorRepo([$existing]);

    $updated = (new UpdateColor($repo, fakeAuditLoggerForColor()))->execute(
        new UpdateColorCommand(id: 1, name: 'Azul Escuro', hex: '#00008B', actorId: 1),
    );

    expect($updated->name())->toBe('Azul Escuro')
        ->and($updated->hex())->toBe('#00008B');
});

it('throws ColorNotFoundException when updating non-existent color', function (): void {
    $repo = fakeColorRepo();

    (new UpdateColor($repo, fakeAuditLoggerForColor()))->execute(
        new UpdateColorCommand(id: 99, name: 'X', hex: null, actorId: 1),
    );
})->throws(ColorNotFoundException::class);

it('deactivates a color', function (): void {
    $existing = new Color(1, 'Azul', null, true);
    $repo = fakeColorRepo([$existing]);

    $result = (new DeactivateColor($repo, fakeAuditLoggerForColor()))->execute(
        new DeactivateColorCommand(id: 1, actorId: 1),
    );

    expect($result->active())->toBeFalse();
});

it('reactivates a color', function (): void {
    $existing = new Color(1, 'Azul', null, false);
    $repo = fakeColorRepo([$existing]);

    $result = (new ReactivateColor($repo, fakeAuditLoggerForColor()))->execute(
        new ReactivateColorCommand(id: 1, actorId: 1),
    );

    expect($result->active())->toBeTrue();
});

it('lists only active colors when filter is applied', function (): void {
    $repo = fakeColorRepo([
        new Color(1, 'Azul', null, true),
        new Color(2, 'Vermelho', null, false),
    ]);

    $results = (new ListColors($repo))->execute(new ListColorsQuery(onlyActive: true));

    expect($results)->toHaveCount(1)
        ->and($results[0]->name())->toBe('Azul');
});

it('lists all colors when filter is not applied', function (): void {
    $repo = fakeColorRepo([
        new Color(1, 'Azul', null, true),
        new Color(2, 'Vermelho', null, false),
    ]);

    $results = (new ListColors($repo))->execute(new ListColorsQuery(onlyActive: false));

    expect($results)->toHaveCount(2);
});
