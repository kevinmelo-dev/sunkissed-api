<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Src\Admin\Infrastructure\Eloquent\AdminModel;
use Src\Catalog\Infrastructure\Eloquent\SizeModel;
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

it('returns 401 on sizes endpoints without token', function (string $method, string $url): void {
    $this->{$method.'Json'}($url)->assertStatus(401);
})->with([
    ['get', '/api/v1/admin/catalog/sizes'],
    ['post', '/api/v1/admin/catalog/sizes'],
    ['patch', '/api/v1/admin/catalog/sizes/1'],
    ['patch', '/api/v1/admin/catalog/sizes/1/deactivate'],
    ['patch', '/api/v1/admin/catalog/sizes/1/reactivate'],
]);

it('creates a size and returns 201', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/sizes', [
            'name' => 'P',
            'sort_order' => 1,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name', 'sort_order', 'active']])
        ->assertJsonPath('data.name', 'P')
        ->assertJsonPath('data.sort_order', 1)
        ->assertJsonPath('data.active', true);
});

it('returns 422 when size name is duplicate', function (): void {
    SizeModel::create(['name' => 'M', 'sort_order' => 2, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/sizes', ['name' => 'M', 'sort_order' => 3]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'duplicate_size_name');
});

it('updates a size', function (): void {
    $size = SizeModel::create(['name' => 'G', 'sort_order' => 3, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/sizes/{$size->id}", [
            'name' => 'GG',
            'sort_order' => 4,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'GG')
        ->assertJsonPath('data.sort_order', 4);
});

it('returns 404 when updating a non-existent size', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/sizes/9999', ['name' => 'X', 'sort_order' => 0]);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'size_not_found');
});

it('deactivates a size', function (): void {
    $size = SizeModel::create(['name' => 'M', 'sort_order' => 2, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/sizes/{$size->id}/deactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', false);
});

it('reactivates a size', function (): void {
    $size = SizeModel::create(['name' => 'M', 'sort_order' => 2, 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/sizes/{$size->id}/reactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', true);
});

it('lists sizes in sort_order sequence', function (): void {
    SizeModel::create(['name' => 'GG', 'sort_order' => 4, 'active' => true]);
    SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    SizeModel::create(['name' => 'M', 'sort_order' => 2, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/sizes');

    $response->assertStatus(200);

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toBe(['P', 'M', 'GG']);
});
