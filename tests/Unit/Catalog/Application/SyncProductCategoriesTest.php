<?php

declare(strict_types=1);

use Src\Catalog\Application\SyncProductCategories\SyncProductCategories;
use Src\Catalog\Application\SyncProductCategories\SyncProductCategoriesCommand;
use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Entity\Product;
use Src\Catalog\Domain\Enum\ProductType;
use Src\Catalog\Domain\Exception\CategoryInactiveException;
use Src\Catalog\Domain\Exception\CategoryNotFoundException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Catalog\Domain\Repository\ProductCategoryRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

function fakeSyncProductRepo(array $products = []): ProductRepository
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

function fakeSyncCategoryRepo(array $categories = []): CategoryRepository
{
    return new class($categories) implements CategoryRepository
    {
        private array $store = [];

        public function __construct(array $categories)
        {
            foreach ($categories as $c) {
                $this->store[$c->id()] = $c;
            }
        }

        public function find(int $id): ?Category
        {
            return $this->store[$id] ?? null;
        }

        public function findBySlug(string $slug): ?Category
        {
            return null;
        }

        public function existsBySlug(string $slug, ?int $excludeId = null): bool
        {
            return false;
        }

        public function hasChildren(int $id): bool
        {
            return false;
        }

        public function all(bool $onlyActive = false): array
        {
            return [];
        }

        public function save(Category $category): Category
        {
            return $category;
        }
    };
}

function fakeProductCategoryRepo(): ProductCategoryRepository
{
    return new class implements ProductCategoryRepository
    {
        public array $synced = [];

        public function categoriesForProduct(int $productId): array
        {
            return [];
        }

        public function sync(int $productId, array $categoryIds): void
        {
            $this->synced[$productId] = $categoryIds;
        }
    };
}

function fakeAuditForSync(): AuditLogger
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

it('syncs categories for a product', function (): void {
    $product = new Product(1, ProductType::Kit, 'Produto', 'produto', null, true);
    $cat1 = Category::createRoot(1, 'Biquínis', 'biquinis');
    $cat2 = Category::createRoot(2, 'Kits', 'kits');
    $pivotRepo = fakeProductCategoryRepo();

    (new SyncProductCategories(
        fakeSyncProductRepo([$product]),
        fakeSyncCategoryRepo([$cat1, $cat2]),
        $pivotRepo,
        fakeAuditForSync(),
    ))->execute(new SyncProductCategoriesCommand(productId: 1, categoryIds: [1, 2], actorId: 1));

    expect($pivotRepo->synced[1])->toBe([1, 2]);
});

it('throws ProductNotFoundException for non-existent product', function (): void {
    (new SyncProductCategories(
        fakeSyncProductRepo(),
        fakeSyncCategoryRepo(),
        fakeProductCategoryRepo(),
        fakeAuditForSync(),
    ))->execute(new SyncProductCategoriesCommand(productId: 99, categoryIds: [], actorId: 1));
})->throws(ProductNotFoundException::class);

it('throws CategoryNotFoundException for non-existent category', function (): void {
    $product = new Product(1, ProductType::Kit, 'Produto', 'produto', null, true);

    (new SyncProductCategories(
        fakeSyncProductRepo([$product]),
        fakeSyncCategoryRepo(),
        fakeProductCategoryRepo(),
        fakeAuditForSync(),
    ))->execute(new SyncProductCategoriesCommand(productId: 1, categoryIds: [99], actorId: 1));
})->throws(CategoryNotFoundException::class);

it('throws CategoryInactiveException for inactive category', function (): void {
    $product = new Product(1, ProductType::Kit, 'Produto', 'produto', null, true);
    $inactive = Category::reconstitute(5, 'Inativa', 'inativa', null, false);

    (new SyncProductCategories(
        fakeSyncProductRepo([$product]),
        fakeSyncCategoryRepo([$inactive]),
        fakeProductCategoryRepo(),
        fakeAuditForSync(),
    ))->execute(new SyncProductCategoriesCommand(productId: 1, categoryIds: [5], actorId: 1));
})->throws(CategoryInactiveException::class);
