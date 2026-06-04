<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $variant_id
 * @property string $type
 * @property int $quantity
 * @property string|null $reason
 * @property string|null $reference
 * @property int|null $parent_movement_id
 * @property string|null $expires_at
 * @property string $created_at
 */
final class InventoryMovementModel extends Model
{
    protected $table = 'inventory_movements';

    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'type',
        'quantity',
        'reason',
        'reference',
        'parent_movement_id',
        'expires_at',
    ];

    public static function booted(): void
    {
        self::creating(function (self $model): void {
            if ($model->created_at === null) {
                $model->created_at = now();
            }
        });
    }
}
