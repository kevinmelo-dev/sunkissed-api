<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\UpdateColor\UpdateColor;
use Src\Catalog\Application\UpdateColor\UpdateColorCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class UpdateColorController
{
    public function __construct(
        private readonly UpdateColor $useCase,
    ) {}

    public function __invoke(UpdateColorRequest $request, int $id): JsonResponse
    {
        $color = $this->useCase->execute(new UpdateColorCommand(
            id: $id,
            name: $request->string('name')->toString(),
            hex: $request->input('hex'),
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
