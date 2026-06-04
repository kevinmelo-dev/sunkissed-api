<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eloquent model for the queryable audit_logs table. Infrastructure detail — the
 * domain never references this class, only the AuditLogRepository interface.
 *
 * @property int $id
 * @property string $action
 * @property string $actor
 * @property string $subject
 * @property string $severity
 * @property array<string, mixed> $context
 * @property int|null $batch_id
 * @property Carbon $occurred_at
 */
final class AuditLogModel extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
        'batch_id' => 'integer',
    ];
}
