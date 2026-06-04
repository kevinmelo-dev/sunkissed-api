<?php

declare(strict_types=1);

namespace Src\Admin\Application\LoginAdmin;

use Src\Admin\Application\Port\AdminTokenIssuer;
use Src\Admin\Application\Port\PasswordVerifier;
use Src\Admin\Domain\Exception\InvalidCredentialsException;
use Src\Admin\Domain\Repository\AdminRepository;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;

final class LoginAdmin
{
    public function __construct(
        private readonly AdminRepository $admins,
        private readonly PasswordVerifier $passwordVerifier,
        private readonly AdminTokenIssuer $tokenIssuer,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(LoginAdminCommand $command): LoginAdminResult
    {
        $admin = $this->admins->findByEmail($command->email);

        if (
            $admin === null
            || ! $this->passwordVerifier->verify($command->password, $admin->password())
            || ! $admin->active()
        ) {
            throw new InvalidCredentialsException;
        }

        $token = $this->tokenIssuer->issue($admin->id());

        $this->audit->log(new AuditEvent(
            action: 'admin.logged_in',
            actor: AuditActor::admin($admin->id()),
            subject: "admin:{$admin->id()}",
        ));

        return new LoginAdminResult(
            token: $token,
            id: $admin->id(),
            name: $admin->name(),
            email: $admin->email(),
        );
    }
}
