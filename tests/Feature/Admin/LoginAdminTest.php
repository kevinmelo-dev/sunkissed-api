<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Src\Admin\Infrastructure\Eloquent\AdminModel;
use Src\Shared\Domain\Audit\AuditLogger;
use Tests\Fakes\FakeAuditLogger;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->fakeAudit = new FakeAuditLogger;
    $this->app->instance(AuditLogger::class, $this->fakeAudit);

    $this->admin = AdminModel::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => Hash::make('secret123'),
        'active' => true,
    ]);
});

it('happy path: returns 200 with token and admin data, and logs audit event', function (): void {
    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'admin@test.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
                'admin' => ['id', 'name', 'email'],
            ],
        ])
        ->assertJsonPath('data.admin.email', 'admin@test.com');

    expect($this->fakeAudit->logged)->toHaveCount(1);
    expect($this->fakeAudit->logged[0]->action)->toBe('admin.logged_in');
    expect($this->fakeAudit->logged[0]->actor->type)->toBe('admin');
    expect($this->fakeAudit->logged[0]->actor->identifier)->toBe((string) $this->admin->id);
});

it('returns 401 with generic PT-BR message when password is wrong', function (): void {
    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'admin@test.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'invalid_credentials')
        ->assertJsonPath('error.message', 'E-mail ou senha inválidos.');
});

it('returns 401 with generic message when email does not exist', function (): void {
    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'nonexistent@test.com',
        'password' => 'whatever',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'invalid_credentials')
        ->assertJsonPath('error.message', 'E-mail ou senha inválidos.');
});

it('returns 401 when admin is inactive', function (): void {
    $this->admin->update(['active' => false]);

    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'admin@test.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'invalid_credentials');
});

it('returns 422 when email is missing', function (): void {
    $this->postJson('/api/v1/admin/login', ['password' => 'secret123'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error')
        ->assertJsonPath('error.details.email.0', 'O campo e-mail é obrigatório.');
});

it('returns 422 when password is missing', function (): void {
    $this->postJson('/api/v1/admin/login', ['email' => 'admin@test.com'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error')
        ->assertJsonPath('error.details.password.0', 'O campo senha é obrigatório.');
});

it('returns 422 when payload is empty', function (): void {
    $this->postJson('/api/v1/admin/login', [])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});
