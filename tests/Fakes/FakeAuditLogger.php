<?php

declare(strict_types=1);

namespace Tests\Fakes;

use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class FakeAuditLogger implements AuditLogger
{
    /** @var AuditEvent[] */
    public array $logged = [];

    public function log(AuditEvent $event): void
    {
        $this->logged[] = $event;
    }

    public function batch(AuditBatchContext $context, string $description, int $total, callable $work): AuditBatch
    {
        throw new \RuntimeException('FakeAuditLogger::batch() not implemented.');
    }
}
