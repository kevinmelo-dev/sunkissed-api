<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Catalog\Application\ImageStorage\ImageStorage;
use Src\Catalog\Application\ListProductColorImages\ListProductColorImages;
use Src\Catalog\Application\ListProductColorImages\ListProductColorImagesQuery;
use Src\Catalog\Application\ListProductColorImages\ProductColorGroup;
use Src\Catalog\Domain\Entity\ProductColorImage;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class ListProductColorImagesController
{
    public function __construct(
        private readonly ListProductColorImages $useCase,
        private readonly ImageStorage $storage,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $groups = $this->useCase->execute(new ListProductColorImagesQuery(productId: $id));

        $data = array_map(function (ProductColorGroup $group): array {
            return [
                'color_id' => $group->colorId,
                'images' => array_map(
                    fn (ProductColorImage $img) => [
                        'id' => $img->id(),
                        'position' => $img->position(),
                        'url' => $this->storage->url($img->storageKey()),
                    ],
                    $group->images,
                ),
            ];
        }, $groups);

        return ApiResponse::success($data);
    }
}
