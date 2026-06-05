<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Src\Admin\Infrastructure\Eloquent\AdminModel;
use Src\Catalog\Infrastructure\Eloquent\CategoryModel;
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

it('returns 401 on category endpoints without token', function (string $method, string $url): void {
    $this->{$method.'Json'}($url)->assertStatus(401);
})->with([
    ['get', '/api/v1/admin/catalog/categories'],
    ['post', '/api/v1/admin/catalog/categories'],
    ['patch', '/api/v1/admin/catalog/categories/1'],
    ['patch', '/api/v1/admin/catalog/categories/1/deactivate'],
    ['patch', '/api/v1/admin/catalog/categories/1/reactivate'],
]);

it('creates a root category and returns 201', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/categories', ['name' => 'Biquínis']);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'parent_id', 'active']])
        ->assertJsonPath('data.name', 'Biquínis')
        ->assertJsonPath('data.slug', 'biquinis')
        ->assertJsonPath('data.parent_id', null)
        ->assertJsonPath('data.active', true);
});

it('creates a subcategory under a root category', function (): void {
    $root = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/categories', [
            'name' => 'Top',
            'parent_id' => $root->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.parent_id', $root->id);
});

it('returns 422 when creating a subcategory under a subcategory', function (): void {
    $root = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);
    $child = CategoryModel::create(['name' => 'Top', 'slug' => 'top', 'parent_id' => $root->id, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/categories', [
            'name' => 'Sub',
            'parent_id' => $child->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_category_hierarchy');
});

it('returns 404 when creating a subcategory with non-existent parent', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/categories', [
            'name' => 'Top',
            'parent_id' => 99999,
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'category_not_found');
});

it('returns 422 when category name (slug) is duplicate', function (): void {
    CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/categories', ['name' => 'Biquínis']);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'duplicate_category_name');
});

it('updates a category', function (): void {
    $cat = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/categories/{$cat->id}", ['name' => 'Maiôs']);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Maiôs')
        ->assertJsonPath('data.slug', 'maios');
});

it('returns 404 when updating non-existent category', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/categories/9999', ['name' => 'X']);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'category_not_found');
});

it('deactivates a category', function (): void {
    $cat = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/categories/{$cat->id}/deactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', false);
});

it('reactivates a category', function (): void {
    $cat = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/categories/{$cat->id}/reactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', true);
});

it('lists categories as a tree with roots and children', function (): void {
    $root = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);
    CategoryModel::create(['name' => 'Top', 'slug' => 'top', 'parent_id' => $root->id, 'active' => true]);
    CategoryModel::create(['name' => 'Bottom', 'slug' => 'bottom', 'parent_id' => $root->id, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/categories');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonCount(2, 'data.0.children');
});

it('lists only active items with filter', function (): void {
    CategoryModel::create(['name' => 'Ativa', 'slug' => 'ativa', 'active' => true]);
    CategoryModel::create(['name' => 'Inativa', 'slug' => 'inativa', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/categories?only_active=1');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('returns 422 when reparenting a root that has children', function (): void {
    $root1 = CategoryModel::create(['name' => 'Raiz Um', 'slug' => 'raiz-um', 'active' => true]);
    CategoryModel::create(['name' => 'Filho', 'slug' => 'filho', 'parent_id' => $root1->id, 'active' => true]);
    $root2 = CategoryModel::create(['name' => 'Raiz Dois', 'slug' => 'raiz-dois', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/categories/{$root1->id}", [
            'name' => 'Raiz Um',
            'parent_id' => $root2->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_category_hierarchy');
});
