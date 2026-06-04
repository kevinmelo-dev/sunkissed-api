<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eloquent model for the audit_batches table. Infrastructure detail — the domain
 * references only the AuditBatchRepository interface.
 *
 * @property int $id
 * @property string $context
 * @property string $description
 * @property int $total
 * @property int $finished
 * @property string $status
 * @property string|null $archive_path
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 */
final class AuditBatchModel extends Model
{
    protected $table = 'audit_batches';

    protected $guarded = [];

    protected $casts = [
        'total' => 'integer',
        'finished' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
