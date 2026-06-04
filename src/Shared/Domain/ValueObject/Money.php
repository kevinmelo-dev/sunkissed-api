<?php

declare(strict_types=1);

namespace Src\Shared\Domain\ValueObject;

use Src\Shared\Domain\Exception\DomainException;

/**
 * Monetary amount stored as integer cents (BRL).
 *
 * Never use floats for money. All prices, totals, and discounts in the system are
 * represented by this value object. Construct from cents, or from a decimal string
 * via fromReais().
 */
final readonly class Money
{
    private function __construct(public int $cents)
    {
        if ($cents < 0) {
            throw new DomainException('Valor monetário não pode ser negativo.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Build from a reais decimal string like "129.90" or "129,90".
     */
    public static function fromReais(string $reais): self
    {
        $normalized = str_replace(',', '.', trim($reais));

        if (! is_numeric($normalized)) {
            throw new DomainException('Valor monetário inválido.');
        }

        return new self((int) round(((float) $normalized) * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(Money $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new DomainException('Quantidade não pode ser negativa.');
        }

        return new self($this->cents * $quantity);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function greaterThan(Money $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents;
    }

    /**
     * Format as a Brazilian reais string, e.g. "R$ 129,90".
     */
    public function format(): string
    {
        return 'R$ '.number_format($this->cents / 100, 2, ',', '.');
    }
}
