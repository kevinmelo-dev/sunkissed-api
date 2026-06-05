<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\UpdateProduct\UpdateProduct;
use Src\Catalog\Application\UpdateProduct\UpdateProductCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class UpdateProductController
{
    public function __construct(
        private readonly UpdateProduct $useCase,
    ) {}

    public function __invoke(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->useCase->execute(new UpdateProductCommand(
            id: $id,
            name: $request->string('name')->toString(),
            slug: $request->string('slug')->toString(),
            description: $request->input('description'),
            type: $request->string('type')->toString(),
            active: (bool) $request->input('active'),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $product->id(),
            'type' => $product->type()->value,
            'name' => $product->name(),
            'slug' => $product->slug(),
            'description' => $product->description(),
            'active' => $product->active(),
        ]);
    }
}
