<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\DeactivateProduct\DeactivateProduct;
use Src\Catalog\Application\DeactivateProduct\DeactivateProductCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class DeactivateProductController
{
    public function __construct(
        private readonly DeactivateProduct $useCase,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $product = $this->useCase->execute(new DeactivateProductCommand(
            id: $id,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $product->id(),
            'active' => $product->active(),
        ]);
    }
}
