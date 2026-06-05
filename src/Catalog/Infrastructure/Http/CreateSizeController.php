<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\CreateSize\CreateSize;
use Src\Catalog\Application\CreateSize\CreateSizeCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class CreateSizeController
{
    public function __construct(
        private readonly CreateSize $useCase,
    ) {}

    public function __invoke(CreateSizeRequest $request): JsonResponse
    {
        $size = $this->useCase->execute(new CreateSizeCommand(
            name: $request->string('name')->toString(),
            sortOrder: $request->integer('sort_order'),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $size->id(),
            'name' => $size->name(),
            'sort_order' => $size->sortOrder(),
            'active' => $size->active(),
        ], status: 201);
    }
}
