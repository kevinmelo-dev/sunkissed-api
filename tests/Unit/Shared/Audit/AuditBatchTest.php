<?php

declare(strict_types=1);

use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditBatchStatus;
use Src\Shared\Domain\Exception\DomainException;

it('opens in pending status with zero progress', function (): void {
    $batch = AuditBatch::open(AuditBatchContext::STOCK_BULK_ADJUSTMENT, 'Ajuste em massa', 10);

    expect($batch->status())->toBe(AuditBatchStatus::PENDING)
        ->and($batch->total())->toBe(10)
        ->and($batch->finished())->toBe(0)
        ->and($batch->startedAt())->toBeNull();
});

it('rejects a negative total', function (): void {
    AuditBatch::open(AuditBatchContext::PRODUCT_IMPORT, 'x', -1);
})->throws(DomainException::class);

it('transitions pending -> running on start', function (): void {
    $batch = AuditBatch::open(AuditBatchContext::PRODUCT_IMPORT, 'Importação', 3);
    $batch->start();

    expect($batch->status())->toBe(AuditBatchStatus::RUNNING)
        ->and($batch->startedAt())->not->toBeNull();
});

it('cannot start a batch twice', function (): void {
    $batch = AuditBatch::open(AuditBatchContext::PRODUCT_IMPORT, 'Importação', 3);
    $batch->start();
    $batch->start();
})->throws(DomainException::class);

it('cannot complete a batch that never started', function (): void {
    $batch = AuditBatch::open(AuditBatchContext::PRODUCT_IMPORT, 'Importação', 3);
    $batch->complete(success: true, archivePath: 'some/path.log');
})->throws(DomainException::class);

it('tracks progress and completes successfully', function (): void {
    $batch = AuditBatch::open(AuditBatchContext::ORDER_IMPORT, 'Importação de pedidos', 3);
    $batch->start();
    $batch->increment();
    $batch->increment(2);
    $batch->complete(success: true, archivePath: 'batches/1.log');

    expect($batch->finished())->toBe(3)
        ->and($batch->status())->toBe(AuditBatchStatus::COMPLETED_SUCCESS)
        ->and($batch->archivePath())->toBe('batches/1.log')
        ->and($batch->endedAt())->not->toBeNull()
        ->and($batch->error())->toBeNull();
});

it('records the error message on failure', function (): void {
    $batch = AuditBatch::open(AuditBatchContext::ORDER_IMPORT, 'Importação', 1);
    $batch->start();
    $batch->complete(success: false, archivePath: null, error: 'Falha na importação');

    expect($batch->status())->toBe(AuditBatchStatus::COMPLETED_ERROR)
        ->and($batch->error())->toBe('Falha na importação');
});

it('requires an id only after persistence', function (): void {
    $batch = AuditBatch::open(AuditBatchContext::ORDER_IMPORT, 'x', 1);
    expect($batch->id())->toBeNull();

    $batch->assignId(42);
    expect($batch->id())->toBe(42)
        ->and($batch->requireId())->toBe(42);
});
