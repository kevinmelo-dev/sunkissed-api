<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\DeactivateSize\DeactivateSize;
use Src\Catalog\Application\DeactivateSize\DeactivateSizeCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class DeactivateSizeController
{
    public function __construct(
        private readonly DeactivateSize $useCase,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $size = $this->useCase->execute(new DeactivateSizeCommand(
            id: $id,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $size->id(),
            'name' => $size->name(),
            'sort_order' => $size->sortOrder(),
            'active' => $size->active(),
        ]);
    }
}
