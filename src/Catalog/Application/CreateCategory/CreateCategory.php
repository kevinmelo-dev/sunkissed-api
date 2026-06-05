<?php

declare(strict_types=1);

namespace Src\Catalog\Application\CreateCategory;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Exception\CategoryNotFoundException;
use Src\Catalog\Domain\Exception\DuplicateCategoryNameException;
use Src\Catalog\Domain\Exception\InvalidCategoryHierarchyException;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class CreateCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(CreateCategoryCommand $command): Category
    {
        if ($this->categories->existsBySlug($command->slug)) {
            throw new DuplicateCategoryNameException($command->name);
        }

        if ($command->parentId !== null) {
            $parent = $this->categories->find($command->parentId);

            if ($parent === null) {
                throw new CategoryNotFoundException($command->parentId);
            }

            if (! $parent->active() || ! $parent->isRoot()) {
                throw new InvalidCategoryHierarchyException;
            }

            $category = Category::createChild(null, $command->name, $command->slug, $parent);
        } else {
            $category = Category::createRoot(null, $command->name, $command->slug);
        }

        $saved = $this->categories->save($category);

        $this->audit->log(new AuditEvent(
            action: 'category.created',
            actor: AuditActor::admin($command->actorId),
            subject: "category:{$saved->id()}",
            context: ['name' => $saved->name(), 'slug' => $saved->slug(), 'parent_id' => $saved->parentId()],
        ));

        return $saved;
    }
}
