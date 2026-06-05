<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ListSizes\ListSizes;
use Src\Catalog\Application\ListSizes\ListSizesQuery;
use Src\Catalog\Domain\Entity\Size;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ListSizesController
{
    public function __construct(
        private readonly ListSizes $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $sizes = $this->useCase->execute(new ListSizesQuery(
            onlyActive: $request->boolean('only_active'),
        ));

        return ApiResponse::success(array_map(
            fn (Size $s) => [
                'id' => $s->id(),
                'name' => $s->name(),
                'sort_order' => $s->sortOrder(),
                'active' => $s->active(),
            ],
            $sizes,
        ));
    }
}
