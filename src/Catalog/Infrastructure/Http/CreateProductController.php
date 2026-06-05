<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\CreateProduct\CreateProduct;
use Src\Catalog\Application\CreateProduct\CreateProductCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class CreateProductController
{
    public function __construct(
        private readonly CreateProduct $useCase,
    ) {}

    public function __invoke(CreateProductRequest $request): JsonResponse
    {
        $product = $this->useCase->execute(new CreateProductCommand(
            type: $request->string('type')->toString(),
            name: $request->string('name')->toString(),
            slug: $request->string('slug')->toString(),
            description: $request->input('description'),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $product->id(),
            'type' => $product->type()->value,
            'name' => $product->name(),
            'slug' => $product->slug(),
            'description' => $product->description(),
            'active' => $product->active(),
        ], status: 201);
    }
}
