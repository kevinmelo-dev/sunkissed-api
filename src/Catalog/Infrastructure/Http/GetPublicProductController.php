<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\GetPublicProduct\GetPublicProduct;
use Src\Catalog\Application\GetPublicProduct\GetPublicProductQuery;
use Src\Catalog\Application\GetPublicProduct\PublicColorEntry;
use Src\Catalog\Domain\Entity\Category;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class GetPublicProductController
{
    public function __construct(
        private readonly GetPublicProduct $useCase,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $detail = $this->useCase->execute(new GetPublicProductQuery(slug: $slug));

        $p = $detail->product;

        return ApiResponse::success([
            'id' => $p->id(),
            'type' => $p->type()->value,
            'name' => $p->name(),
            'slug' => $p->slug(),
            'description' => $p->description(),
            'cover_image_url' => $detail->coverImageUrl,
            'categories' => array_map(
                fn (Category $c) => [
                    'id' => $c->id(),
                    'name' => $c->name(),
                    'slug' => $c->slug(),
                ],
                $detail->categories,
            ),
            'colors' => array_map(
                fn (PublicColorEntry $entry) => [
                    'id' => $entry->color->id(),
                    'name' => $entry->color->name(),
                    'hex' => $entry->color->hex(),
                    'images' => $entry->images,
                    'sizes' => $entry->sizes,
                ],
                $detail->colors,
            ),
        ]);
    }
}
