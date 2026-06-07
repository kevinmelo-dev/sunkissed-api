<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Src\Catalog\Infrastructure\Eloquent\ColorModel;
use Src\Catalog\Infrastructure\Eloquent\ProductColorImageModel;
use Src\Catalog\Infrastructure\Eloquent\ProductModel;
use Src\Catalog\Infrastructure\Eloquent\ProductVariantModel;
use Src\Catalog\Infrastructure\Eloquent\SizeModel;
use Src\Shared\Domain\Audit\AuditLogger;
use Tests\Fakes\FakeAuditLogger;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
    Storage::fake('images');
    $this->app->instance(AuditLogger::class, new FakeAuditLogger);
});

// ─── Public routes require no auth ───────────────────────────────────────────

it('lists active products publicly without a token', function (): void {
    ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);

    $this->getJson('/api/v1/catalog/products')->assertStatus(200);
});

it('returns 404 for non-existent product slug on storefront', function (): void {
    $this->getJson('/api/v1/catalog/products/nao-existe')->assertStatus(404);
});

// ─── List products ────────────────────────────────────────────────────────────

it('only returns active products in storefront list', function (): void {
    ProductModel::create(['type' => 'kit', 'name' => 'Ativo', 'slug' => 'ativo', 'active' => true]);
    ProductModel::create(['type' => 'single', 'name' => 'Inativo', 'slug' => 'inativo', 'active' => false]);

    $response = $this->getJson('/api/v1/catalog/products');

    $response->assertStatus(200)->assertJsonCount(1, 'data');
    expect($response->json('data.0.slug'))->toBe('ativo');
});

it('includes cover_image_url in storefront list', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);

    Storage::disk('images')->put('products/1/colors/1/img.jpg', 'fake');
    ProductColorImageModel::create([
        'product_id' => $product->id, 'color_id' => $color->id,
        'storage_key' => 'products/1/colors/1/img.jpg', 'position' => 1,
    ]);

    $response = $this->getJson('/api/v1/catalog/products');

    $response->assertStatus(200);
    expect($response->json('data.0.cover_image_url'))->not->toBeNull();
});

it('returns null cover_image_url when product has no images', function (): void {
    ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);

    $response = $this->getJson('/api/v1/catalog/products');

    $response->assertStatus(200);
    expect($response->json('data.0.cover_image_url'))->toBeNull();
});

// ─── Get single product ───────────────────────────────────────────────────────

it('returns inactive product as 404 on storefront', function (): void {
    ProductModel::create(['type' => 'kit', 'name' => 'Inativo', 'slug' => 'inativo', 'active' => false]);

    $this->getJson('/api/v1/catalog/products/inativo')->assertStatus(404);
});

it('returns product detail grouped by color with sizes', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $c1 = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $c2 = ColorModel::create(['name' => 'Azul', 'active' => true]);
    $s1 = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    $s2 = SizeModel::create(['name' => 'M', 'sort_order' => 2, 'active' => true]);

    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $c1->id, 'size_id' => $s1->id,
        'sku' => 'V1', 'price_cents' => 19900, 'active' => true,
    ]);
    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $c1->id, 'size_id' => $s2->id,
        'sku' => 'V2', 'price_cents' => 19900, 'active' => true,
    ]);
    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $c2->id, 'size_id' => $s1->id,
        'sku' => 'V3', 'price_cents' => 21900, 'active' => true,
    ]);

    ProductColorImageModel::create([
        'product_id' => $product->id, 'color_id' => $c1->id,
        'storage_key' => 'k1', 'position' => 1,
    ]);

    $response = $this->getJson("/api/v1/catalog/products/{$product->slug}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'slug', 'cover_image_url', 'colors']]);

    $colors = $response->json('data.colors');
    expect(count($colors))->toBe(2);

    $rosa = collect($colors)->firstWhere('name', 'Rosa');
    expect(count($rosa['sizes']))->toBe(2)
        ->and(count($rosa['images']))->toBe(1);
});

it('excludes inactive variants from storefront', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $s1 = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    $s2 = SizeModel::create(['name' => 'M', 'sort_order' => 2, 'active' => true]);

    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $color->id, 'size_id' => $s1->id,
        'sku' => 'VA', 'price_cents' => 0, 'active' => true,
    ]);
    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $color->id, 'size_id' => $s2->id,
        'sku' => 'VB', 'price_cents' => 0, 'active' => false,
    ]);

    $response = $this->getJson("/api/v1/catalog/products/{$product->slug}");

    $response->assertStatus(200);
    $colors = $response->json('data.colors');
    $rosa = $colors[0];
    expect(count($rosa['sizes']))->toBe(1);
});

it('reflects stock availability in storefront sizes', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'Biquíni', 'slug' => 'biquini', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);

    $variant = ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $color->id, 'size_id' => $size->id,
        'sku' => 'V1', 'price_cents' => 0, 'active' => true,
    ]);

    // No inventory movements => stock = 0 => not available
    $response = $this->getJson("/api/v1/catalog/products/{$product->slug}");

    $response->assertStatus(200);
    $size = $response->json('data.colors.0.sizes.0');
    expect($size['available'])->toBeFalse();
});

it('cover_color_id drives cover image selection', function (): void {
    $c1 = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $c2 = ColorModel::create(['name' => 'Azul', 'active' => true]);
    $product = ProductModel::create([
        'type' => 'kit', 'name' => 'P', 'slug' => 'p',
        'active' => true, 'cover_color_id' => $c2->id,
    ]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);

    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $c2->id, 'size_id' => $size->id,
        'sku' => 'V1', 'price_cents' => 0, 'active' => true,
    ]);

    ProductColorImageModel::create([
        'product_id' => $product->id, 'color_id' => $c1->id,
        'storage_key' => 'rosa.jpg', 'position' => 1,
    ]);
    ProductColorImageModel::create([
        'product_id' => $product->id, 'color_id' => $c2->id,
        'storage_key' => 'azul.jpg', 'position' => 1,
    ]);

    $response = $this->getJson('/api/v1/catalog/products/p');

    $response->assertStatus(200);
    expect($response->json('data.cover_image_url'))->toContain('azul.jpg');
});
