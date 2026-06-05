<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\DeactivateColor\DeactivateColor;
use Src\Catalog\Application\DeactivateColor\DeactivateColorCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class DeactivateColorController
{
    public function __construct(
        private readonly DeactivateColor $useCase,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $color = $this->useCase->execute(new DeactivateColorCommand(
            id: $id,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $color->id(),
            'name' => $color->name(),
            'hex' => $color->hex(),
            'active' => $color->active(),
        ]);
    }
}
