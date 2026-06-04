<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * Lifecycle of a bulk operation tracked for observability. Mirrors the proven
 * batch model from production, with explicit states instead of magic integers.
 */
enum AuditBatchStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED_SUCCESS = 'completed_success';
    case COMPLETED_ERROR = 'completed_error';
}
