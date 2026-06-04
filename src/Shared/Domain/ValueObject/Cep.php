<?php

declare(strict_types=1);

namespace Src\Shared\Domain\ValueObject;

use Src\Shared\Domain\Exception\DomainException;

/**
 * Brazilian postal code (CEP). Stored normalized as 8 digits, formatted as 00000-000.
 */
final readonly class Cep
{
    private function __construct(public string $digits) {}

    public static function fromString(string $value): self
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($digits) !== 8) {
            throw new DomainException('CEP inválido. Informe 8 dígitos.');
        }

        return new self($digits);
    }

    public function format(): string
    {
        return substr($this->digits, 0, 5).'-'.substr($this->digits, 5);
    }

    public function equals(Cep $other): bool
    {
        return $this->digits === $other->digits;
    }
}
