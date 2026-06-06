<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Src\Admin\Infrastructure\Eloquent\AdminModel;
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
    Storage::fake('s3');
    $this->app->instance(AuditLogger::class, new FakeAuditLogger);

    $this->admin = AdminModel::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => Hash::make('secret'),
        'active' => true,
    ]);
});

// ─── Guard protection ─────────────────────────────────────────────────────────

it('returns 401 on image endpoints without token', function (string $method, string $url): void {
    $this->{$method.'Json'}($url)->assertStatus(401);
})->with([
    ['post', '/api/v1/admin/catalog/products/1/colors/1/images'],
    ['get', '/api/v1/admin/catalog/products/1/images'],
    ['put', '/api/v1/admin/catalog/products/1/colors/1/images/order'],
    ['delete', '/api/v1/admin/catalog/products/1/images/1'],
    ['put', '/api/v1/admin/catalog/products/1/cover-color'],
]);

// ─── Upload ───────────────────────────────────────────────────────────────────

it('uploads a valid image and returns 201 with position', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $color->id, 'size_id' => $size->id,
        'sku' => 'P1C1S1', 'price_cents' => 0, 'active' => true,
    ]);

    $file = UploadedFile::fake()->image('foto.jpg', 100, 100)->mimeType('image/jpeg');

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson("/api/v1/admin/catalog/products/{$product->id}/colors/{$color->id}/images", [
            'image' => $file,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.product_id', $product->id)
        ->assertJsonPath('data.color_id', $color->id)
        ->assertJsonPath('data.position', 1);

    expect(ProductColorImageModel::count())->toBe(1);
});

it('assigns incremental position for second upload', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $color->id, 'size_id' => $size->id,
        'sku' => 'SKU1', 'price_cents' => 0, 'active' => true,
    ]);

    $file = UploadedFile::fake()->image('a.jpg')->mimeType('image/jpeg');

    $this->actingAs($this->admin, 'admin')
        ->postJson("/api/v1/admin/catalog/products/{$product->id}/colors/{$color->id}/images", ['image' => $file]);

    $file2 = UploadedFile::fake()->image('b.jpg')->mimeType('image/jpeg');
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson("/api/v1/admin/catalog/products/{$product->id}/colors/{$color->id}/images", ['image' => $file2]);

    $response->assertStatus(201)->assertJsonPath('data.position', 2);
});

it('rejects non-image file by MIME type', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);

    // A text/plain file with a .jpg extension — mimetypes rule rejects it
    $file = UploadedFile::fake()->create('shell.jpg', 10, 'text/plain');

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson("/api/v1/admin/catalog/products/{$product->id}/colors/{$color->id}/images", [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});

it('rejects image over 5 MB', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);

    $file = UploadedFile::fake()->image('big.jpg')->size(6000);

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson("/api/v1/admin/catalog/products/{$product->id}/colors/{$color->id}/images", [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});

it('returns 404 when product not found on upload', function (): void {
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $file = UploadedFile::fake()->image('a.jpg')->mimeType('image/jpeg');

    $response = $this->actingAs($this->admin, 'admin')
        ->postJson("/api/v1/admin/catalog/products/9999/colors/{$color->id}/images", ['image' => $file]);

    $response->assertStatus(404);
});

// ─── Reorder ─────────────────────────────────────────────────────────────────

it('reorders images', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);

    $img1 = ProductColorImageModel::create([
        'product_id' => $product->id, 'color_id' => $color->id,
        'storage_key' => 'k1', 'position' => 1,
    ]);
    $img2 = ProductColorImageModel::create([
        'product_id' => $product->id, 'color_id' => $color->id,
        'storage_key' => 'k2', 'position' => 2,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/colors/{$color->id}/images/order", [
            'image_ids' => [$img2->id, $img1->id],
        ]);

    $response->assertStatus(200);

    expect(ProductColorImageModel::find($img2->id)->position)->toBe(1)
        ->and(ProductColorImageModel::find($img1->id)->position)->toBe(2);
});

// ─── Delete ───────────────────────────────────────────────────────────────────

it('deletes an image from storage and database', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);

    Storage::disk('s3')->put('products/1/colors/1/img.jpg', 'fake content');

    $img = ProductColorImageModel::create([
        'product_id' => $product->id, 'color_id' => $color->id,
        'storage_key' => 'products/1/colors/1/img.jpg', 'position' => 1,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->deleteJson("/api/v1/admin/catalog/products/{$product->id}/images/{$img->id}");

    $response->assertStatus(200);

    expect(ProductColorImageModel::find($img->id))->toBeNull();
    Storage::disk('s3')->assertMissing('products/1/colors/1/img.jpg');
});

it('returns 404 when deleting non-existent image', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->deleteJson("/api/v1/admin/catalog/products/{$product->id}/images/9999");

    $response->assertStatus(404);
});

// ─── Set cover color ──────────────────────────────────────────────────────────

it('sets the cover color for a product', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1, 'active' => true]);
    ProductVariantModel::create([
        'product_id' => $product->id, 'color_id' => $color->id, 'size_id' => $size->id,
        'sku' => 'SKU-C', 'price_cents' => 0, 'active' => true,
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/cover-color", [
            'color_id' => $color->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.cover_color_id', $color->id);
});

it('returns 422 with PT-BR message when cover color is inactive', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => false]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/cover-color", [
            'color_id' => $color->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'color_inactive');
});

it('returns 422 when cover color is not associated with the product', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $color = ColorModel::create(['name' => 'Rosa', 'active' => true]);

    $response = $this->actingAs($this->admin, 'admin')
        ->putJson("/api/v1/admin/catalog/products/{$product->id}/cover-color", [
            'color_id' => $color->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'color_not_associated_with_product');
});

// ─── List images ──────────────────────────────────────────────────────────────

it('lists images grouped by color', function (): void {
    $product = ProductModel::create(['type' => 'kit', 'name' => 'P', 'slug' => 'p', 'active' => true]);
    $c1 = ColorModel::create(['name' => 'Rosa', 'active' => true]);
    $c2 = ColorModel::create(['name' => 'Azul', 'active' => true]);

    ProductColorImageModel::create(['product_id' => $product->id, 'color_id' => $c1->id, 'storage_key' => 'k1', 'position' => 1]);
    ProductColorImageModel::create(['product_id' => $product->id, 'color_id' => $c1->id, 'storage_key' => 'k2', 'position' => 2]);
    ProductColorImageModel::create(['product_id' => $product->id, 'color_id' => $c2->id, 'storage_key' => 'k3', 'position' => 1]);

    $response = $this->actingAs($this->admin, 'admin')
        ->getJson("/api/v1/admin/catalog/products/{$product->id}/images");

    $response->assertStatus(200);
    $data = $response->json('data');

    expect(count($data))->toBe(2);

    $group1 = collect($data)->firstWhere('color_id', $c1->id);
    expect(count($group1['images']))->toBe(2);
});
