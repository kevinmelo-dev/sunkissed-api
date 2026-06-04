<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * Who performed an audited action. Modeled as a typed value object instead of a free
 * string so actors are consistent across contexts and render uniformly in the trail
 * (e.g. "admin:3", "customer:42", "system:webhook", "system:scheduler").
 */
final readonly class AuditActor
{
    private function __construct(
        public string $type,
        public string $identifier,
    ) {}

    public static function admin(int|string $id): self
    {
        return new self('admin', (string) $id);
    }

    public static function customer(int|string $id): self
    {
        return new self('customer', (string) $id);
    }

    /**
     * A non-human actor: a webhook, a scheduled job, a queue worker.
     */
    public static function system(string $process): self
    {
        return new self('system', $process);
    }

    public function toString(): string
    {
        return "{$this->type}:{$this->identifier}";
    }
}
