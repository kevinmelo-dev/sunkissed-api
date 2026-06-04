<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Catalog\Application\RegisterStockEntry\RegisterStockEntry;
use Src\Catalog\Application\RegisterStockEntry\RegisterStockEntryCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class RegisterStockEntryController
{
    public function __construct(
        private readonly RegisterStockEntry $useCase,
    ) {}

    public function __invoke(RegisterStockEntryRequest $request): JsonResponse
    {
        $command = new RegisterStockEntryCommand(
            variantId: $request->integer('variant_id'),
            quantity: $request->integer('quantity'),
            reason: $request->input('reason'),
            actorId: $request->user()->id,
        );

        $result = $this->useCase->execute($command);

        return ApiResponse::success([
            'movement_id' => $result->movementId,
            'available_after' => $result->availableAfter,
        ], status: 201);
    }
}
