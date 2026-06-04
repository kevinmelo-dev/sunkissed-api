<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Exception;

/**
 * Base class for all domain (business-rule) exceptions.
 *
 * Domain code throws subclasses of this. The framework's exception handler is
 * responsible for translating them into the standard HTTP error envelope — domain
 * code must never build an HTTP response. Messages are in Brazilian Portuguese
 * because they are surfaced to end users.
 */
class DomainException extends \DomainException
{
    /**
     * HTTP status this exception maps to. Subclasses may override.
     */
    public function httpStatus(): int
    {
        return 422;
    }

    /**
     * Stable machine-readable error code for the API envelope.
     */
    public function errorCode(): string
    {
        return 'domain_error';
    }
}
