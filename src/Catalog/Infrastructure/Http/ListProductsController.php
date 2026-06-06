<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ListProducts\ListProducts;
use Src\Catalog\Application\ListProducts\ListProductsQuery;
use Src\Catalog\Application\ProductCoverImage\ProductCoverImageResolver;
use Src\Catalog\Domain\Entity\Product;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ListProductsController
{
    public function __construct(
        private readonly ListProducts $useCase,
        private readonly ProductCoverImageResolver $coverResolver,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $products = $this->useCase->execute(
            new ListProductsQuery(onlyActive: (bool) $request->query('only_active', false)),
        );

        return ApiResponse::success(array_map(
            fn (Product $p) => [
                'id' => $p->id(),
                'type' => $p->type()->value,
                'name' => $p->name(),
                'slug' => $p->slug(),
                'description' => $p->description(),
                'active' => $p->active(),
                'cover_image_url' => $this->coverResolver->resolve($p),
            ],
            $products,
        ));
    }
}
