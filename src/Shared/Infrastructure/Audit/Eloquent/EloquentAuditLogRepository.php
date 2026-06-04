<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit\Eloquent;

use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogRepository;

final class EloquentAuditLogRepository implements AuditLogRepository
{
    public function store(AuditEvent $event): void
    {
        AuditLogModel::query()->create($this->toRow($event));
    }

    public function storeMany(array $events): void
    {
        if ($events === []) {
            return;
        }

        $rows = array_map(fn (AuditEvent $e): array => $this->toRow($e), $events);

        // Single multi-row insert. context is stored as JSON via the model cast when
        // using create(); for insert() we encode explicitly.
        AuditLogModel::query()->insert(array_map(function (array $row): array {
            $row['context'] = json_encode($row['context'], JSON_UNESCAPED_UNICODE);

            return $row;
        }, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(AuditEvent $event): array
    {
        return [
            'action' => $event->action,
            'actor' => $event->actor->toString(),
            'subject' => $event->subject,
            'severity' => $event->severity->value,
            'context' => $event->context,
            'batch_id' => $event->batchId,
            'occurred_at' => $event->occurredAtOrNow()->format('Y-m-d H:i:s'),
        ];
    }
}
