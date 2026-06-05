<?php

declare(strict_types=1);

use Src\Catalog\Application\CreateProduct\CreateProduct;
use Src\Catalog\Application\CreateProduct\CreateProductCommand;
use Src\Catalog\Application\DeactivateProduct\DeactivateProduct;
use Src\Catalog\Application\DeactivateProduct\DeactivateProductCommand;
use Src\Catalog\Application\ReactivateProduct\ReactivateProduct;
use Src\Catalog\Application\ReactivateProduct\ReactivateProductCommand;
use Src\Catalog\Application\UpdateProduct\UpdateProduct;
use Src\Catalog\Application\UpdateProduct\UpdateProductCommand;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Exception\DuplicateProductSlugException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

function fakeProductRepo(array $products = []): ProductRepository
{
    return new class($products) implements ProductRepository
    {
        /** @var Product[] */
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
            foreach ($this->store as $p) {
                if ($p->slug() === $slug) {
                    return $p;
                }
            }

            return null;
        }

        public function all(bool $onlyActive = false): array
        {
            return array_values(array_filter(
                $this->store,
                fn (Product $p) => ! $onlyActive || $p->active(),
            ));
        }

        public function existsBySlug(string $slug, ?int $excludeId = null): bool
        {
            foreach ($this->store as $id => $p) {
                if ($p->slug() === $slug && $id !== $excludeId) {
                    return true;
                }
            }

            return false;
        }

        public function save(Product $product): Product
        {
            $id = $product->id() ?? $this->nextId++;
            $saved = new Product($id, $product->type(), $product->name(), $product->slug(), $product->description(), $product->active());
            $this->store[$id] = $saved;

            return $saved;
        }
    };
}

function fakeAuditForProduct(): AuditLogger
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

it('creates a product and returns it with an id', function (): void {
    $repo = fakeProductRepo();
    $product = (new CreateProduct($repo, fakeAuditForProduct()))->execute(
        new CreateProductCommand(type: 'kit', name: 'Biquíni Floral', slug: 'biquini-floral', description: null, actorId: 1),
    );

    expect($product->id())->not->toBeNull()
        ->and($product->name())->toBe('Biquíni Floral')
        ->and($product->slug())->toBe('biquini-floral')
        ->and($product->type())->toBe(ProductType::Kit)
        ->and($product->active())->toBeTrue();
});

it('throws DuplicateProductSlugException when slug already exists', function (): void {
    $existing = new Product(1, ProductType::Kit, 'Biquíni Floral', 'biquini-floral', null, true);
    $repo = fakeProductRepo([$existing]);

    (new CreateProduct($repo, fakeAuditForProduct()))->execute(
        new CreateProductCommand(type: 'single', name: 'Outro', slug: 'biquini-floral', description: null, actorId: 1),
    );
})->throws(DuplicateProductSlugException::class);

it('updates a product', function (): void {
    $existing = new Product(1, ProductType::Kit, 'Biquíni Floral', 'biquini-floral', null, true);
    $repo = fakeProductRepo([$existing]);

    $updated = (new UpdateProduct($repo, fakeAuditForProduct()))->execute(
        new UpdateProductCommand(id: 1, name: 'Biquíni Azul', slug: 'biquini-azul', description: 'Lindo', type: 'kit', active: true, actorId: 1),
    );

    expect($updated->name())->toBe('Biquíni Azul')
        ->and($updated->slug())->toBe('biquini-azul')
        ->and($updated->description())->toBe('Lindo');
});

it('throws ProductNotFoundException when updating non-existent product', function (): void {
    $repo = fakeProductRepo();

    (new UpdateProduct($repo, fakeAuditForProduct()))->execute(
        new UpdateProductCommand(id: 99, name: 'X', slug: 'x', description: null, type: 'kit', active: true, actorId: 1),
    );
})->throws(ProductNotFoundException::class);

it('update rejects duplicate slug from another product', function (): void {
    $p1 = new Product(1, ProductType::Kit, 'Produto 1', 'produto-1', null, true);
    $p2 = new Product(2, ProductType::Single, 'Produto 2', 'produto-2', null, true);
    $repo = fakeProductRepo([$p1, $p2]);

    (new UpdateProduct($repo, fakeAuditForProduct()))->execute(
        new UpdateProductCommand(id: 2, name: 'Produto 2', slug: 'produto-1', description: null, type: 'single', active: true, actorId: 1),
    );
})->throws(DuplicateProductSlugException::class);

it('deactivates a product', function (): void {
    $existing = new Product(1, ProductType::Kit, 'Biquíni', 'biquini', null, true);
    $repo = fakeProductRepo([$existing]);

    $result = (new DeactivateProduct($repo, fakeAuditForProduct()))->execute(
        new DeactivateProductCommand(id: 1, actorId: 1),
    );

    expect($result->active())->toBeFalse();
});

it('reactivates a product', function (): void {
    $existing = new Product(1, ProductType::Kit, 'Biquíni', 'biquini', null, false);
    $repo = fakeProductRepo([$existing]);

    $result = (new ReactivateProduct($repo, fakeAuditForProduct()))->execute(
        new ReactivateProductCommand(id: 1, actorId: 1),
    );

    expect($result->active())->toBeTrue();
});
