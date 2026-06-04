<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

use DateTimeImmutable;
use Src\Shared\Domain\Exception\DomainException;

/**
 * Tracks a bulk operation for observability: how many items it intended to process,
 * how many it finished, its status, timing, and the location of its archived log.
 *
 * This is the clean evolution of the production "batch" table. State transitions are
 * explicit and guarded — you cannot finish a batch that never started, etc. The id is
 * assigned by the repository on persistence.
 */
final class AuditBatch
{
    private function __construct(
        public readonly AuditBatchContext $context,
        public readonly string $description,
        private int $total,
        private int $finished,
        private AuditBatchStatus $status,
        private ?DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $endedAt,
        private ?string $archivePath,
        private ?string $error,
        private ?int $id,
    ) {}

    public static function open(AuditBatchContext $context, string $description, int $total): self
    {
        if ($total < 0) {
            throw new DomainException('O total de itens do lote não pode ser negativo.');
        }

        return new self(
            context: $context,
            description: $description,
            total: $total,
            finished: 0,
            status: AuditBatchStatus::PENDING,
            startedAt: null,
            endedAt: null,
            archivePath: null,
            error: null,
            id: null,
        );
    }

    public function start(): void
    {
        if ($this->status !== AuditBatchStatus::PENDING) {
            throw new DomainException('Apenas um lote pendente pode ser iniciado.');
        }

        $this->status = AuditBatchStatus::RUNNING;
        $this->startedAt = new DateTimeImmutable;
    }

    public function increment(int $by = 1): void
    {
        $this->finished += $by;
    }

    public function adjustTotal(int $total): void
    {
        if ($total < 0) {
            throw new DomainException('O total de itens do lote não pode ser negativo.');
        }

        $this->total = $total;
    }

    public function complete(bool $success, ?string $archivePath, ?string $error = null): void
    {
        if ($this->status !== AuditBatchStatus::RUNNING) {
            throw new DomainException('Apenas um lote em execução pode ser finalizado.');
        }

        $this->status = $success
            ? AuditBatchStatus::COMPLETED_SUCCESS
            : AuditBatchStatus::COMPLETED_ERROR;
        $this->endedAt = new DateTimeImmutable;
        $this->archivePath = $archivePath;
        $this->error = $error;
    }

    public function assignId(int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        if ($this->id === null) {
            throw new DomainException('Lote ainda não persistido.');
        }

        return $this->id;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function finished(): int
    {
        return $this->finished;
    }

    public function status(): AuditBatchStatus
    {
        return $this->status;
    }

    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function archivePath(): ?string
    {
        return $this->archivePath;
    }

    public function error(): ?string
    {
        return $this->error;
    }
}
