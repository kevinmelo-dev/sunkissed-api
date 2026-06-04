<?php

declare(strict_types=1);

namespace Src\Admin\Domain\Entity;

final class Admin
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly string $email,
        private readonly string $password,
        private readonly bool $active,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
