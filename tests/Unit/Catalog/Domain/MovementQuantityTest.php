<?php

declare(strict_types=1);

use Src\Catalog\Domain\Enum\MovementType;
use Src\Catalog\Domain\ValueObject\MovementQuantity;
use Src\Shared\Domain\Exception\DomainException;

it('accepts positive quantity for entrada', function (): void {
    $qty = new MovementQuantity(5, MovementType::Entrada);
    expect($qty->value)->toBe(5);
});

it('rejects zero for any type', function (): void {
    new MovementQuantity(0, MovementType::Entrada);
})->throws(DomainException::class);

it('rejects negative quantity for non-ajuste types', function (MovementType $type): void {
    new MovementQuantity(-1, $type);
})->throws(DomainException::class)
    ->with([
        [MovementType::Entrada],
        [MovementType::Saida],
        [MovementType::Reserva],
        [MovementType::Liberacao],
    ]);

it('allows negative quantity for ajuste', function (): void {
    $qty = new MovementQuantity(-3, MovementType::Ajuste);
    expect($qty->value)->toBe(-3)
        ->and($qty->absolute())->toBe(3);
});
