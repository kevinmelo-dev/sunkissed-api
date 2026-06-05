<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ReactivateProduct\ReactivateProduct;
use Src\Catalog\Application\ReactivateProduct\ReactivateProductCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ReactivateProductController
{
    public function __construct(
        private readonly ReactivateProduct $useCase,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $product = $this->useCase->execute(new ReactivateProductCommand(
            id: $id,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $product->id(),
            'active' => $product->active(),
        ]);
    }
}
