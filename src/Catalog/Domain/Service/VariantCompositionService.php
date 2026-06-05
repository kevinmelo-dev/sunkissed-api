<?php

declare(strict_types=1);

namespace Src\Catalog\Domain\Service;

use Src\Catalog\Domain\Entity\ProductVariant;

/**
 * Pure domain service. Given the full list of current variants for a product and the
 * desired color × size selection, computes exactly what to create, reactivate, and
 * deactivate. No framework dependency; fully testable without infrastructure.
 */
final class VariantCompositionService
{
    /**
     * @param  ProductVariant[]  $currentVariants  All variants (active + inactive) for the product
     * @param  int[]  $desiredColorIds
     * @param  int[]  $desiredSizeIds
     */
    public function compose(
        array $currentVariants,
        array $desiredColorIds,
        array $desiredSizeIds,
    ): VariantCompositionResult {
        $desired = [];
        foreach ($desiredColorIds as $colorId) {
            foreach ($desiredSizeIds as $sizeId) {
                $desired["{$colorId}:{$sizeId}"] = ['colorId' => $colorId, 'sizeId' => $sizeId];
            }
        }

        $existingByCombo = [];
        foreach ($currentVariants as $variant) {
            $key = "{$variant->colorId()}:{$variant->sizeId()}";
            $existingByCombo[$key] = $variant;
        }

        $toReactivate = [];
        $toCreate = [];

        foreach ($desired as $key => $combo) {
            if (isset($existingByCombo[$key])) {
                $variant = $existingByCombo[$key];
                if (! $variant->active()) {
                    $toReactivate[] = $variant;
                }
            } else {
                $toCreate[] = $combo;
            }
        }

        $toDeactivate = [];
        foreach ($currentVariants as $variant) {
            $key = "{$variant->colorId()}:{$variant->sizeId()}";
            if ($variant->active() && ! isset($desired[$key])) {
                $toDeactivate[] = $variant;
            }
        }

        return new VariantCompositionResult(
            toReactivate: $toReactivate,
            toDeactivate: $toDeactivate,
            toCreate: $toCreate,
        );
    }
}
