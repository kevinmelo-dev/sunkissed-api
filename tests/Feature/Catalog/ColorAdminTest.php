<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Src\Admin\Infrastructure\Eloquent\AdminModel;
use Src\Catalog\Infrastructure\Eloquent\ColorModel;
use Src\Shared\Domain\Audit\AuditLogger;
use Tests\Fakes\FakeAuditLogger;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->app->instance(AuditLogger::class, new FakeAuditLogger);

    $this->admin = AdminModel::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => Hash::make('secret'),
        'active' => true,
    ]);
});

it('returns 401 on colors endpoints without token', function (string $method, string $url): void {
    $this->{$method.'Json'}($url)->assertStatus(401);
})->with([
    ['get', '/api/v1/admin/catalog/colors'],
    ['post', '/api/v1/admin/catalog/colors'],
    ['patch', '/api/v1/admin/catalog/colors/1'],
    ['patch', '/api/v1/admin/catalog/colors/1/deactivate'],
    ['patch', '/api/v1/admin/catalog/colors/1/reactivate'],
]);

it('creates a color and returns 201 with the standard envelope', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/colors', [
            'name' => 'Azul Marinho',
            'hex' => '#001F5B',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name', 'hex', 'active']])
        ->assertJsonPath('data.name', 'Azul Marinho')
        ->assertJsonPath('data.hex', '#001F5B')
        ->assertJsonPath('data.active', true);
});

it('creates a color without hex', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/colors', ['name' => 'Branco']);

    $response->assertStatus(201)
        ->assertJsonPath('data.hex', null);
});

it('returns 422 with PT-BR message on invalid hex format', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/colors', ['name' => 'Cor', 'hex' => 'invalid']);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});

it('returns 422 when color name is duplicate', function (): void {
    ColorModel::create(['name' => 'Azul', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/colors', ['name' => 'Azul']);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'duplicate_color_name');
});

it('updates a color', function (): void {
    $color = ColorModel::create(['name' => 'Azul', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/colors/{$color->id}", [
            'name' => 'Azul Claro',
            'hex' => '#ADD8E6',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Azul Claro')
        ->assertJsonPath('data.hex', '#ADD8E6');
});

it('returns 404 when updating a non-existent color', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/colors/9999', ['name' => 'X']);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'color_not_found');
});

it('deactivates a color', function (): void {
    $color = ColorModel::create(['name' => 'Verde', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/colors/{$color->id}/deactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', false);
});

it('reactivates a color', function (): void {
    $color = ColorModel::create(['name' => 'Verde', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/colors/{$color->id}/reactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', true);
});

it('lists all colors including inactive when no filter', function (): void {
    ColorModel::create(['name' => 'Ativo', 'active' => true]);
    ColorModel::create(['name' => 'Inativo', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/colors');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('lists only active colors with filter', function (): void {
    ColorModel::create(['name' => 'Ativo', 'active' => true]);
    ColorModel::create(['name' => 'Inativo', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/colors?only_active=1');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});
