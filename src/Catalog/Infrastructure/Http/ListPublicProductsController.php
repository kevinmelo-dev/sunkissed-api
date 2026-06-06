<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ListPublicProducts\ListPublicProducts;
use Src\Catalog\Application\ListPublicProducts\ListPublicProductsQuery;
use Src\Catalog\Application\ListPublicProducts\PublicProductSummary;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ListPublicProductsController
{
    public function __construct(
        private readonly ListPublicProducts $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $summaries = $this->useCase->execute(new ListPublicProductsQuery);

        return ApiResponse::success(array_map(
            fn (PublicProductSummary $s) => [
                'id' => $s->product->id(),
                'type' => $s->product->type()->value,
                'name' => $s->product->name(),
                'slug' => $s->product->slug(),
                'description' => $s->product->description(),
                'cover_image_url' => $s->coverImageUrl,
            ],
            $summaries,
        ));
    }
}
