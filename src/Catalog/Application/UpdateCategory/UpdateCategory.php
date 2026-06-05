<?php

declare(strict_types=1);

namespace Src\Catalog\Application\UpdateCategory;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Exception\CategoryNotFoundException;
use Src\Catalog\Domain\Exception\DuplicateCategoryNameException;
use Src\Catalog\Domain\Exception\InvalidCategoryHierarchyException;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class UpdateCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(UpdateCategoryCommand $command): Category
    {
        $existing = $this->categories->find($command->id);

        if ($existing === null) {
            throw new CategoryNotFoundException($command->id);
        }

        if ($this->categories->existsBySlug($command->slug, $command->id)) {
            throw new DuplicateCategoryNameException($command->name);
        }

        if ($command->parentId !== null) {
            if ($this->categories->hasChildren($command->id)) {
                throw new InvalidCategoryHierarchyException;
            }

            $parent = $this->categories->find($command->parentId);

            if ($parent === null) {
                throw new CategoryNotFoundException($command->parentId);
            }

            if (! $parent->active() || ! $parent->isRoot()) {
                throw new InvalidCategoryHierarchyException;
            }

            $updated = Category::createChild($existing->id(), $command->name, $command->slug, $parent, $existing->active());
        } else {
            $updated = Category::createRoot($existing->id(), $command->name, $command->slug, $existing->active());
        }

        $saved = $this->categories->save($updated);

        $this->audit->log(new AuditEvent(
            action: 'category.updated',
            actor: AuditActor::admin($command->actorId),
            subject: "category:{$saved->id()}",
            context: ['name' => $saved->name(), 'slug' => $saved->slug(), 'parent_id' => $saved->parentId()],
        ));

        return $saved;
    }
}
