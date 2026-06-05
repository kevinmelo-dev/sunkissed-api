<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\GetProduct\GetProduct;
use Src\Catalog\Application\GetProduct\GetProductQuery;
use Src\Catalog\Domain\Entity\Category;
use Src\Catalog\Domain\Entity\ProductVariant;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class GetProductController
{
    public function __construct(
        private readonly GetProduct $useCase,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $detail = $this->useCase->execute(new GetProductQuery(id: $id));

        $p = $detail->product;

        return ApiResponse::success([
            'id' => $p->id(),
            'type' => $p->type()->value,
            'name' => $p->name(),
            'slug' => $p->slug(),
            'description' => $p->description(),
            'active' => $p->active(),
            'categories' => array_map(
                fn (Category $c) => [
                    'id' => $c->id(),
                    'name' => $c->name(),
                    'slug' => $c->slug(),
                ],
                $detail->categories,
            ),
            'variants' => array_map(
                fn (ProductVariant $v) => [
                    'id' => $v->id(),
                    'sku' => $v->sku()->value,
                    'color_id' => $v->colorId(),
                    'size_id' => $v->sizeId(),
                    'price_cents' => $v->price()->cents,
                    'image' => $v->image(),
                    'active' => $v->active(),
                ],
                $detail->variants,
            ),
        ]);
    }
}
