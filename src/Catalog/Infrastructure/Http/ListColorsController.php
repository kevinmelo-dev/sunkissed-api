<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ListColors\ListColors;
use Src\Catalog\Application\ListColors\ListColorsQuery;
use Src\Catalog\Domain\Entity\Color;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ListColorsController
{
    public function __construct(
        private readonly ListColors $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $colors = $this->useCase->execute(new ListColorsQuery(
            onlyActive: $request->boolean('only_active'),
        ));

        return ApiResponse::success(array_map(
            fn (Color $c) => [
                'id' => $c->id(),
                'name' => $c->name(),
                'hex' => $c->hex(),
                'active' => $c->active(),
            ],
            $colors,
        ));
    }
}
