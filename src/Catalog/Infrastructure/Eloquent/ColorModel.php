<?php

declare(strict_types=1);

namespace Src\Catalog\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $hex
 * @property bool $active
 */
final class ColorModel extends Model
{
    protected $table = 'colors';

    protected $fillable = ['name', 'hex', 'active'];

    protected $casts = ['active' => 'boolean'];
}
