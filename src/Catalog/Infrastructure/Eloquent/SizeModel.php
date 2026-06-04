<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property bool $active
 */
final class SizeModel extends Model
{
    protected $table = 'sizes';

    protected $fillable = ['name', 'sort_order', 'active'];

    protected $casts = ['active' => 'boolean'];
}
