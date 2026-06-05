<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ListCategories\CategoryTreeItem;
use Src\Catalog\Application\ListCategories\ListCategories;
use Src\Catalog\Application\ListCategories\ListCategoriesQuery;
use Src\Catalog\Domain\Entity\Category;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ListCategoriesController
{
    public function __construct(
        private readonly ListCategories $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->useCase->execute(new ListCategoriesQuery(
            onlyActive: $request->boolean('only_active'),
        ));

        return ApiResponse::success(array_map(
            fn (CategoryTreeItem $item) => [
                'id' => $item->root->id(),
                'name' => $item->root->name(),
                'slug' => $item->root->slug(),
                'active' => $item->root->active(),
                'children' => array_map(
                    fn (Category $child) => [
                        'id' => $child->id(),
                        'name' => $child->name(),
                        'slug' => $child->slug(),
                        'active' => $child->active(),
                    ],
                    $item->children,
                ),
            ],
            $items,
        ));
    }
}
