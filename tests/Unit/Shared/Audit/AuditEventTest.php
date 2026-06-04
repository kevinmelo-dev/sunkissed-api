<?php

declare(strict_types=1);

use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditSeverity;

it('renders actors as typed identifiers', function (): void {
    expect(AuditActor::admin(3)->toString())->toBe('admin:3')
        ->and(AuditActor::customer(42)->toString())->toBe('customer:42')
        ->and(AuditActor::system('webhook')->toString())->toBe('system:webhook');
});

it('serializes to the canonical array form', function (): void {
    $event = new AuditEvent(
        action: 'order.status_changed',
        actor: AuditActor::system('webhook'),
        subject: 'order:1042',
        context: ['from' => 'aguardando_pagamento', 'to' => 'pago'],
        severity: AuditSeverity::INFO,
        batchId: null,
    );

    $array = $event->toArray();

    expect($array['action'])->toBe('order.status_changed')
        ->and($array['actor'])->toBe('system:webhook')
        ->and($array['subject'])->toBe('order:1042')
        ->and($array['severity'])->toBe('info')
        ->and($array['context'])->toBe(['from' => 'aguardando_pagamento', 'to' => 'pago'])
        ->and($array['batch_id'])->toBeNull()
        ->and($array['occurred_at'])->toBeString();
});

it('preserves UTF-8 content when encoded as JSON', function (): void {
    $event = new AuditEvent(
        action: 'product.created',
        actor: AuditActor::admin(1),
        subject: 'product:7',
        context: ['name' => 'Conjunto Princesa — Coração Rosa ✨'],
    );

    $json = json_encode($event->toArray(), JSON_UNESCAPED_UNICODE);

    // The accented/emoji content survives intact (no ISO-8859-1 mangling, no \uXXXX).
    expect($json)->toContain('Coração Rosa ✨');
});

it('defaults occurredAt to now when not provided', function (): void {
    $event = new AuditEvent(
        action: 'x',
        actor: AuditActor::system('test'),
        subject: 'thing:1',
    );

    expect($event->occurredAtOrNow())->toBeInstanceOf(DateTimeImmutable::class);
});
