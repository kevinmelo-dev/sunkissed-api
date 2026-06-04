<?php

declare(strict_types=1);

namespace Src\Shared\Domain\ValueObject;

use Src\Shared\Domain\Exception\DomainException;

/**
 * Brazilian phone number, normalized to E.164 (e.g. +5521969689782).
 * Used as a verified contact for passwordless OTP authentication.
 */
final readonly class Phone
{
    private function __construct(public string $e164) {}

    public static function fromString(string $value): self
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        // Strip a leading country code if already present.
        if (str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        // Expect DDD (2) + number (8 or 9 digits).
        if (strlen($digits) < 10 || strlen($digits) > 11) {
            throw new DomainException('Número de telefone inválido.');
        }

        return new self('+55'.$digits);
    }

    public function equals(Phone $other): bool
    {
        return $this->e164 === $other->e164;
    }
}
