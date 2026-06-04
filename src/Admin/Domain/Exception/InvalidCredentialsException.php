<?php

declare(strict_types=1);

namespace Src\Admin\Domain\Exception;

use Src\Shared\Domain\Exception\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('E-mail ou senha inválidos.');
    }

    public function httpStatus(): int
    {
        return 401;
    }

    public function errorCode(): string
    {
        return 'invalid_credentials';
    }
}
