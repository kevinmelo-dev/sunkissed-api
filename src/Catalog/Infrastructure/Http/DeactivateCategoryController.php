<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\DeactivateCategory\DeactivateCategory;
use Src\Catalog\Application\DeactivateCategory\DeactivateCategoryCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class DeactivateCategoryController
{
    public function __construct(
        private readonly DeactivateCategory $useCase,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $category = $this->useCase->execute(new DeactivateCategoryCommand(
            id: $id,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $category->id(),
            'name' => $category->name(),
            'slug' => $category->slug(),
            'parent_id' => $category->parentId(),
            'active' => $category->active(),
        ]);
    }
}
