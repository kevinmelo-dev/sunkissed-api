<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * Severity of an audit event. Kept small and meaningful — audit events record
 * business-significant actions, not application debug noise (that stays in the
 * regular logger). INFO is the default; WARNING/ERROR/CRITICAL flag events that
 * deserve operator attention.
 */
enum AuditSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
}
