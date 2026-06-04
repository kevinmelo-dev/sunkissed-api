<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * Persists audit events to the queryable store (the audit_logs table). The Eloquent
 * implementation lives in Infrastructure. Kept narrow on purpose — querying/reading
 * for the admin screen is a separate read concern and does not belong here.
 */
interface AuditLogRepository
{
    public function store(AuditEvent $event): void;

    /**
     * Persist many events at once (used when flushing a batch's buffered events).
     *
     * @param  array<int, AuditEvent>  $events
     */
    public function storeMany(array $events): void;
}
