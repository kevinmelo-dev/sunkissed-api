<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

use DateTimeImmutable;

/**
 * A single audit event: who did what, to which subject, with what context.
 *
 * `subject` is a loose, namespaced identifier so any context can record an event
 * without coupling to another context's models (e.g. "order:1042", "variant:88",
 * "payment:mp_127766"). `batchId` links the event to a bulk operation when one is
 * in progress (see AuditBatch). The event is immutable once created.
 */
final readonly class AuditEvent
{
    /**
     * @param  array<string, mixed>  $context  Structured detail. Must be JSON-serializable.
     */
    public function __construct(
        public string $action,
        public AuditActor $actor,
        public string $subject,
        public array $context = [],
        public AuditSeverity $severity = AuditSeverity::INFO,
        public ?int $batchId = null,
        public ?DateTimeImmutable $occurredAt = null,
    ) {}

    public function occurredAtOrNow(): DateTimeImmutable
    {
        return $this->occurredAt ?? new DateTimeImmutable;
    }

    /**
     * Canonical array form, used for both the queryable row and the archived JSON line.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'actor' => $this->actor->toString(),
            'subject' => $this->subject,
            'severity' => $this->severity->value,
            'context' => $this->context,
            'batch_id' => $this->batchId,
            'occurred_at' => $this->occurredAtOrNow()->format(DATE_ATOM),
        ];
    }
}
