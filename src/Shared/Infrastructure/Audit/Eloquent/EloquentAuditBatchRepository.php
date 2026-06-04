<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit\Eloquent;

use DateTimeImmutable;
use ReflectionClass;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditBatchRepository;
use Src\Shared\Domain\Audit\AuditBatchStatus;

final class EloquentAuditBatchRepository implements AuditBatchRepository
{
    public function save(AuditBatch $batch): void
    {
        $attributes = [
            'context' => $batch->context->value,
            'description' => $batch->description,
            'total' => $batch->total(),
            'finished' => $batch->finished(),
            'status' => $batch->status()->value,
            'archive_path' => $batch->archivePath(),
            'error' => $batch->error(),
            'started_at' => $batch->startedAt()?->format('Y-m-d H:i:s'),
            'ended_at' => $batch->endedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($batch->id() === null) {
            $model = AuditBatchModel::query()->create($attributes);
            $batch->assignId((int) $model->id);

            return;
        }

        AuditBatchModel::query()->whereKey($batch->id())->update($attributes);
    }

    public function findById(int $id): ?AuditBatch
    {
        $model = AuditBatchModel::query()->find($id);

        if ($model === null) {
            return null;
        }

        return $this->reconstitute($model);
    }

    /**
     * Rebuild the domain aggregate from a persisted row. AuditBatch has a private
     * constructor (it is created via open()), so we hydrate through reflection to keep
     * the domain API clean rather than adding a public "fromState" just for the ORM.
     */
    private function reconstitute(AuditBatchModel $model): AuditBatch
    {
        $reflection = new ReflectionClass(AuditBatch::class);
        /** @var AuditBatch $batch */
        $batch = $reflection->newInstanceWithoutConstructor();

        $set = function (string $property, mixed $value) use ($reflection, $batch): void {
            $prop = $reflection->getProperty($property);
            $prop->setValue($batch, $value);
        };

        $set('context', AuditBatchContext::from($model->context));
        $set('description', $model->description);
        $set('total', $model->total);
        $set('finished', $model->finished);
        $set('status', AuditBatchStatus::from($model->status));
        $set('startedAt', $model->started_at
            ? new DateTimeImmutable($model->started_at->format('Y-m-d H:i:s'))
            : null);
        $set('endedAt', $model->ended_at
            ? new DateTimeImmutable($model->ended_at->format('Y-m-d H:i:s'))
            : null);
        $set('archivePath', $model->archive_path);
        $set('error', $model->error);
        $set('id', (int) $model->id);

        return $batch;
    }
}
