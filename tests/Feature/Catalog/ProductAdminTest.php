<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Src\Admin\Infrastructure\Eloquent\AdminModel;
use Src\Catalog\Infrastructure\Eloquent\CategoryModel;
use Src\Catalog\Infrastructure\Eloquent\ColorModel;
use Src\Catalog\Infrastructure\Eloquent\ProductModel;
use Src\Catalog\Infrastructure\Eloquent\ProductVariantModel;
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

// ─── Guard protection ────────────────────────────────────────────────────────

it('returns 401 on product endpoints without token', function (string $method, string $url): void {
    $this->{$method.'Json'}($url)->assertStatus(401);
})->with([
    ['get', '/api/v1/admin/catalog/products'],
    ['post', '/api/v1/admin/catalog/products'],
    ['get', '/api/v1/admin/catalog/products/1'],
    ['patch', '/api/v1/admin/catalog/products/1'],
    ['patch', '/api/v1/admin/catalog/products/1/deactivate'],
    ['patch', '/api/v1/admin/catalog/products/1/reactivate'],
    ['put', '/api/v1/admin/catalog/products/1/categories'],
    ['put', '/api/v1/admin/catalog/products/1/variants/compose'],
    ['patch', '/api/v1/admin/catalog/variants/1'],
]);

// ─── Create ─────────────────────────────────────────────────────────────────

it('creates a product and returns 201 with the standard envelope', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/products', [
            'type' => 'kit',
            'name' => 'Biquíni Floral',
            'slug' => 'biquini-floral',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'type', 'name', 'slug', 'description', 'active']])
        ->assertJsonPath('data.name', 'Biquíni Floral')
        ->assertJsonPath('data.slug', 'biquini-floral')
        ->assertJsonPath('data.type', 'kit')
        ->assertJsonPath('data.active', true);
});

it('returns 422 with PT-BR message on duplicate slug', function (): void {
    ProductModel::create(['type' => 'kit', 'name' => 'Produto', 'slug' => 'produto', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/products', [
            'type' => 'single',
            'name' => 'Outro Produto',
            'slug' => 'produto',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'duplicate_product_slug');
});

it('returns 422 for invalid slug format', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/api/v1/admin/catalog/products', [
            'type' => 'kit',
            'name' => 'Produto',
            'slug' => 'Slug Inválido!',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});

// ─── Update ──────────────────────────────────────────────────────────────────

it('updates a product', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Original', 'slug' => 'original', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/products/{$product->id}", [
            'type' => 'single',
            'name' => 'Atualizado',
            'slug' => 'atualizado',
            'active' => true,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Atualizado')
        ->assertJsonPath('data.type', 'single');
});

it('returns 404 when updating non-existent product', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/products/9999', [
            'type' => 'kit',
            'name' => 'X',
            'slug' => 'x',
            'active' => true,
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'product_not_found');
});

// ─── Deactivate / Reactivate ─────────────────────────────────────────────────

it('deactivates a product', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Ativo', 'slug' => 'ativo', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/products/{$product->id}/deactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', false);
});

it('reactivates a product', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Inativo', 'slug' => 'inativo', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/products/{$product->id}/reactivate");

    $response->assertStatus(200)
        ->assertJsonPath('data.active', true);
});

// ─── List & Get ──────────────────────────────────────────────────────────────

it('lists all products including inactive', function (): void {
    ProductModel::create(['type' => 'kit', 'name' => 'Ativo', 'slug' => 'ativo', 'active' => true]);
    ProductModel::create(['type' => 'single', 'name' => 'Inativo', 'slug' => 'inativo', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/products');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('lists only active products with filter', function (): void {
    ProductModel::create(['type' => 'kit', 'name' => 'Ativo', 'slug' => 'ativo', 'active' => true]);
    ProductModel::create(['type' => 'single', 'name' => 'Inativo', 'slug' => 'inativo', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/products?only_active=1');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('returns product detail with variants and categories', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    $category = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);

    ProductVariantModel::create([
        'product_id' => $product->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'sku' => "P{$product->id}C{$color->id}S{$size->id}",
        'price_cents' => 0,
        'active' => true,
    ]);

    $product->categories()->attach($category->id);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson("/api/v1/admin/catalog/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'variants', 'categories']])
        ->assertJsonCount(1, 'data.variants')
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.name', 'Biquínis');
});

it('returns 404 for non-existent product detail', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->getJson('/api/v1/admin/catalog/products/9999');

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'product_not_found');
});

// ─── Sync Categories ─────────────────────────────────────────────────────────

it('syncs product categories', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $cat1 = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);
    $cat2 = CategoryModel::create(['name' => 'Kits', 'slug' => 'kits', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/categories", [
            'category_ids' => [$cat1->id, $cat2->id],
        ]);

    $response->assertStatus(200);
    expect($product->categories()->count())->toBe(2);
});

it('returns 404 when syncing categories for non-existent product', function (): void {
    $cat = CategoryModel::create(['name' => 'Biquínis', 'slug' => 'biquinis', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson('/api/v1/admin/catalog/products/9999/categories', [
            'category_ids' => [$cat->id],
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'product_not_found');
});

it('returns 404 when syncing with non-existent category', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/categories", [
            'category_ids' => [9999],
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'category_not_found');
});

it('returns 422 when syncing with inactive category', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $inactive = CategoryModel::create(['name' => 'Inativa', 'slug' => 'inativa', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/categories", [
            'category_ids' => [$inactive->id],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'category_inactive');
});

// ─── Compose Variants ────────────────────────────────────────────────────────

it('composes 6 variants from 2 colors × 3 sizes', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $c1 = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $c2 = ColorModel::create(['name' => 'Azul', 'active' => true]);
    $s1 = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    $s2 = SizeModel::create(['name' => 'M', 'sort_order' => 2, 'active' => true]);
    $s3 = SizeModel::create(['name' => 'G', 'sort_order' => 3, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/variants/compose", [
            'color_ids' => [$c1->id, $c2->id],
            'size_ids' => [$s1->id, $s2->id, $s3->id],
        ]);

    $response->assertStatus(200);
    expect(ProductVariantModel::where('product_id', $product->id)->count())->toBe(6);
    expect(ProductVariantModel::where('product_id', $product->id)->where('active', true)->count())->toBe(6);
});

it('returns 422 when composing with inactive color', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $inactive = ColorModel::create(['name' => 'Inativa', 'active' => false]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/variants/compose", [
            'color_ids' => [$inactive->id],
            'size_ids' => [$size->id],
        ]);

    $response->assertStatus(404);
});

// ─── Update Variant ──────────────────────────────────────────────────────────

it('updates variant price', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    $variant = ProductVariantModel::create([
        'product_id' => $product->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'sku' => "P{$product->id}C{$color->id}S{$size->id}",
        'price_cents' => 0,
        'active' => true,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/variants/{$variant->id}", [
            'price_cents' => 19900,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.price_cents', 19900);
});

it('returns 422 with PT-BR message when sku is duplicate', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $color2 = ColorModel::create(['name' => 'Azul', 'active' => true]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);

    $v1 = ProductVariantModel::create([
        'product_id' => $product->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'sku' => 'SKU-A',
        'price_cents' => 0,
        'active' => true,
    ]);
    ProductVariantModel::create([
        'product_id' => $product->id,
        'color_id' => $color2->id,
        'size_id' => $size->id,
        'sku' => 'SKU-B',
        'price_cents' => 0,
        'active' => true,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson("/api/v1/admin/catalog/variants/{$v1->id}", [
            'sku' => 'SKU-B',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'duplicate_sku');
});

it('returns 404 for non-existent variant', function (): void {
    $response = $this->actingAs($this->admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/variants/9999', [
            'price_cents' => 1000,
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'product_variant_not_found');
});
