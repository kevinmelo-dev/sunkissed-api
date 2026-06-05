<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ReactivateColor\ReactivateColor;
use Src\Catalog\Application\ReactivateColor\ReactivateColorCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ReactivateColorController
{
    public function __construct(
        private readonly ReactivateColor $useCase,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $color = $this->useCase->execute(new ReactivateColorCommand(
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
