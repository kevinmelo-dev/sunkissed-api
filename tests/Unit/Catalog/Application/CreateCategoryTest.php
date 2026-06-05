<?php

declare(strict_types=1);

use Src\Catalog\Application\CreateCategory\CreateCategory;
use Src\Catalog\Application\CreateCategory\CreateCategoryCommand;
use Src\Catalog\Application\ListCategories\ListCategories;
use Src\Catalog\Application\ListCategories\ListCategoriesQuery;
use Src\Catalog\Application\UpdateCategory\UpdateCategory;
use Src\Catalog\Application\UpdateCategory\UpdateCategoryCommand;
use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Exception\CategoryNotFoundException;
use Src\Catalog\Domain\Exception\DuplicateCategoryNameException;
use Src\Catalog\Domain\Exception\InvalidCategoryHierarchyException;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

function fakeCategoryRepo(array $categories = []): CategoryRepository
{
    return new class($categories) implements CategoryRepository
    {
        /** @var Category[] */
        private array $store = [];

        private int $nextId = 1;

        public function __construct(array $categories)
        {
            foreach ($categories as $c) {
                $id = $c->id() ?? $this->nextId++;
                $this->store[$id] = $c;
            }
        }

        public function find(int $id): ?Category
        {
            return $this->store[$id] ?? null;
        }

        public function findBySlug(string $slug): ?Category
        {
            foreach ($this->store as $c) {
                if ($c->slug() === $slug) {
                    return $c;
                }
            }

            return null;
        }

        public function existsBySlug(string $slug, ?int $excludeId = null): bool
        {
            foreach ($this->store as $id => $c) {
                if ($c->slug() === $slug && $id !== $excludeId) {
                    return true;
                }
            }

            return false;
        }

        public function hasChildren(int $id): bool
        {
            foreach ($this->store as $c) {
                if ($c->parentId() === $id) {
                    return true;
                }
            }

            return false;
        }

        public function all(bool $onlyActive = false): array
        {
            return array_values(array_filter(
                $this->store,
                fn (Category $c) => ! $onlyActive || $c->active(),
            ));
        }

        public function save(Category $category): Category
        {
            $id = $category->id() ?? $this->nextId++;
            $saved = Category::reconstitute($id, $category->name(), $category->slug(), $category->parentId(), $category->active());
            $this->store[$id] = $saved;

            return $saved;
        }
    };
}

function fakeAuditLoggerForCategory(): AuditLogger
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

it('creates a root category', function (): void {
    $repo = fakeCategoryRepo();
    $cat = (new CreateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new CreateCategoryCommand(name: 'Biquínis', slug: 'biquinis', parentId: null, actorId: 1),
    );

    expect($cat->id())->not->toBeNull()
        ->and($cat->name())->toBe('Biquínis')
        ->and($cat->slug())->toBe('biquinis')
        ->and($cat->parentId())->toBeNull()
        ->and($cat->active())->toBeTrue();
});

it('creates a subcategory under a root category', function (): void {
    $root = Category::reconstitute(1, 'Biquínis', 'biquinis', null, true);
    $repo = fakeCategoryRepo([$root]);

    $child = (new CreateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new CreateCategoryCommand(name: 'Top', slug: 'top', parentId: 1, actorId: 1),
    );

    expect($child->parentId())->toBe(1);
});

it('throws DuplicateCategoryNameException when slug already exists', function (): void {
    $existing = Category::reconstitute(1, 'Biquínis', 'biquinis', null, true);
    $repo = fakeCategoryRepo([$existing]);

    (new CreateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new CreateCategoryCommand(name: 'Biquínis', slug: 'biquinis', parentId: null, actorId: 1),
    );
})->throws(DuplicateCategoryNameException::class);

it('throws CategoryNotFoundException when parent does not exist', function (): void {
    $repo = fakeCategoryRepo();

    (new CreateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new CreateCategoryCommand(name: 'Top', slug: 'top', parentId: 99, actorId: 1),
    );
})->throws(CategoryNotFoundException::class);

it('throws InvalidCategoryHierarchyException when parent is a subcategory', function (): void {
    $root = Category::reconstitute(1, 'Biquínis', 'biquinis', null, true);
    $child = Category::reconstitute(2, 'Top', 'top', 1, true);
    $repo = fakeCategoryRepo([$root, $child]);

    (new CreateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new CreateCategoryCommand(name: 'Sub', slug: 'sub', parentId: 2, actorId: 1),
    );
})->throws(InvalidCategoryHierarchyException::class);

it('throws InvalidCategoryHierarchyException when parent is inactive', function (): void {
    $inactiveRoot = Category::reconstitute(1, 'Biquínis', 'biquinis', null, false);
    $repo = fakeCategoryRepo([$inactiveRoot]);

    (new CreateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new CreateCategoryCommand(name: 'Top', slug: 'top', parentId: 1, actorId: 1),
    );
})->throws(InvalidCategoryHierarchyException::class);

it('updates a category name', function (): void {
    $cat = Category::reconstitute(1, 'Biquínis', 'biquinis', null, true);
    $repo = fakeCategoryRepo([$cat]);

    $updated = (new UpdateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new UpdateCategoryCommand(id: 1, name: 'Maiôs', slug: 'maios', parentId: null, actorId: 1),
    );

    expect($updated->name())->toBe('Maiôs')
        ->and($updated->slug())->toBe('maios');
});

it('throws when reparenting a root category that has children', function (): void {
    $root = Category::reconstitute(1, 'Raiz', 'raiz', null, true);
    $child = Category::reconstitute(2, 'Filho', 'filho', 1, true);
    $otherRoot = Category::reconstitute(3, 'Outra', 'outra', null, true);
    $repo = fakeCategoryRepo([$root, $child, $otherRoot]);

    (new UpdateCategory($repo, fakeAuditLoggerForCategory()))->execute(
        new UpdateCategoryCommand(id: 1, name: 'Raiz', slug: 'raiz', parentId: 3, actorId: 1),
    );
})->throws(InvalidCategoryHierarchyException::class);

it('ListCategories builds tree structure from flat list', function (): void {
    $root = Category::reconstitute(1, 'Biquínis', 'biquinis', null, true);
    $child = Category::reconstitute(2, 'Top', 'top', 1, true);
    $repo = fakeCategoryRepo([$root, $child]);

    $tree = (new ListCategories($repo))->execute(new ListCategoriesQuery);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]->root->id())->toBe(1)
        ->and($tree[0]->children)->toHaveCount(1)
        ->and($tree[0]->children[0]->id())->toBe(2);
});
