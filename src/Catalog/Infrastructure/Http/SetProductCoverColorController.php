<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\SetProductCoverColor\SetProductCoverColor;
use Src\Catalog\Application\SetProductCoverColor\SetProductCoverColorCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class SetProductCoverColorController
{
    public function __construct(
        private readonly SetProductCoverColor $useCase,
    ) {}

    public function __invoke(SetProductCoverColorRequest $request, int $id): JsonResponse
    {
        $product = $this->useCase->execute(new SetProductCoverColorCommand(
            productId: $id,
            colorId: (int) $request->input('color_id'),
            actorId: $request->user()->id,
        ));

        return ApiResponse::success([
            'id' => $product->id(),
            'cover_color_id' => $product->coverColorId(),
        ]);
    }
}
