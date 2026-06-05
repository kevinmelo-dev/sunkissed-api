<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\UpdateProductVariant\UpdateProductVariant;
use Src\Catalog\Application\UpdateProductVariant\UpdateProductVariantCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class UpdateProductVariantController
{
    public function __construct(
        private readonly UpdateProductVariant $useCase,
    ) {}

    public function __invoke(UpdateProductVariantRequest $request, int $id): JsonResponse
    {
        $variant = $this->useCase->execute(new UpdateProductVariantCommand(
            id: $id,
            priceCents: $request->has('price_cents') ? (int) $request->input('price_cents') : null,
            image: $request->has('image') ? $request->input('image') : null,
            sku: $request->has('sku') ? $request->string('sku')->toString() : null,
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $variant->id(),
            'sku' => $variant->sku()->value,
            'color_id' => $variant->colorId(),
            'size_id' => $variant->sizeId(),
            'price_cents' => $variant->price()->cents,
            'image' => $variant->image(),
            'active' => $variant->active(),
        ]);
    }
}
