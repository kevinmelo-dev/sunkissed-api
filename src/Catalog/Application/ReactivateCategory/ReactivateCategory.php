<?php

declare(strict_types=1);

namespace Src\Catalog\Application\ReactivateCategory;

use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Exception\CategoryNotFoundException;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class ReactivateCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(ReactivateCategoryCommand $command): Category
    {
        $existing = $this->categories->find($command->id);

        if ($existing === null) {
            throw new CategoryNotFoundException($command->id);
        }

        $reactivated = Category::reconstitute(
            id: $existing->id(),
            name: $existing->name(),
            slug: $existing->slug(),
            parentId: $existing->parentId(),
            active: true,
        );

        $saved = $this->categories->save($reactivated);

        $this->audit->log(new AuditEvent(
            action: 'category.reactivated',
            actor: AuditActor::admin($command->actorId),
            subject: "category:{$saved->id()}",
            context: ['name' => $saved->name()],
        ));

        return $saved;
    }
}
