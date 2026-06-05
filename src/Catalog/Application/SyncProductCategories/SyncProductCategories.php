<?php

declare(strict_types=1);

namespace Src\Catalog\Application\SyncProductCategories;

use Src\Catalog\Domain\Exception\CategoryInactiveException;
use Src\Catalog\Domain\Exception\CategoryNotFoundException;
use Src\Catalog\Domain\Exception\ProductNotFoundException;
use Src\Catalog\Domain\Repository\CategoryRepository;
use Src\Catalog\Domain\Repository\ProductCategoryRepository;
use Src\Catalog\Domain\Repository\ProductRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class SyncProductCategories
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly ProductCategoryRepository $productCategories,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(SyncProductCategoriesCommand $command): void
    {
        $product = $this->products->find($command->productId);

        if ($product === null) {
            throw new ProductNotFoundException($command->productId);
        }

        foreach ($command->categoryIds as $categoryId) {
            $category = $this->categories->find($categoryId);

            if ($category === null) {
                throw new CategoryNotFoundException($categoryId);
            }

            if (! $category->active()) {
                throw new CategoryInactiveException($categoryId);
            }
        }

        $this->productCategories->sync($command->productId, $command->categoryIds);

        $this->audit->log(new AuditEvent(
            action: 'product.categories_synced',
            actor: AuditActor::admin($command->actorId),
            subject: "product:{$command->productId}",
            context: ['category_ids' => $command->categoryIds],
        ));
    }
}
