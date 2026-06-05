<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\CreateColor\CreateColor;
use Src\Catalog\Application\CreateColor\CreateColorCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class CreateColorController
{
    public function __construct(
        private readonly CreateColor $useCase,
    ) {}

    public function __invoke(CreateColorRequest $request): JsonResponse
    {
        $color = $this->useCase->execute(new CreateColorCommand(
            name: $request->string('name')->toString(),
            hex: $request->input('hex'),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $color->id(),
            'name' => $color->name(),
            'hex' => $color->hex(),
            'active' => $color->active(),
        ], status: 201);
    }
}
