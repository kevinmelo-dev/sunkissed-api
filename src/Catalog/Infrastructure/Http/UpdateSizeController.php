<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\UpdateSize\UpdateSize;
use Src\Catalog\Application\UpdateSize\UpdateSizeCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class UpdateSizeController
{
    public function __construct(
        private readonly UpdateSize $useCase,
    ) {}

    public function __invoke(UpdateSizeRequest $request, int $id): JsonResponse
    {
        $size = $this->useCase->execute(new UpdateSizeCommand(
            id: $id,
            name: $request->string('name')->toString(),
            sortOrder: $request->integer('sort_order'),
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
