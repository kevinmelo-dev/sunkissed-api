<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Src\Catalog\Application\CreateCategory\CreateCategory;
use Src\Catalog\Application\CreateCategory\CreateCategoryCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class CreateCategoryController
{
    public function __construct(
        private readonly CreateCategory $useCase,
    ) {}

    public function __invoke(CreateCategoryRequest $request): JsonResponse
    {
        $name = $request->string('name')->toString();

        $category = $this->useCase->execute(new CreateCategoryCommand(
            name: $name,
            slug: Str::slug($name),
            parentId: $request->input('parent_id') !== null ? $request->integer('parent_id') : null,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $category->id(),
            'name' => $category->name(),
            'slug' => $category->slug(),
            'parent_id' => $category->parentId(),
            'active' => $category->active(),
        ], status: 201);
    }
}
