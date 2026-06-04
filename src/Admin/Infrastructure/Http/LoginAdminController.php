<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Admin\Application\LoginAdmin\LoginAdmin;
use Src\Admin\Application\LoginAdmin\LoginAdminCommand;
use Src\Shared\Infrastructure\Http\ApiResponse;

final class LoginAdminController
{
    public function __construct(
        private readonly LoginAdmin $useCase,
    ) {}

    public function __invoke(LoginAdminRequest $request): JsonResponse
    {
        $command = new LoginAdminCommand(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        );

        $result = $this->useCase->execute($command);

        return ApiResponse::success([
            'token' => $result->token,
            'admin' => [
                'id' => $result->id,
                'name' => $result->name,
                'email' => $result->email,
            ],
        ]);
    }
}
