<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Src\Catalog\Infrastructure\Eloquent\ColorModel;
use Src\Catalog\Infrastructure\Eloquent\ProductModel;
use Src\Catalog\Infrastructure\Eloquent\ProductVariantModel;
use Src\Catalog\Infrastructure\Eloquent\SizeModel;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
    $color = ColorModel::create(['name' => 'Azul', 'hex' => '#0000FF']);
    $size = SizeModel::create(['name' => 'P', 'sort_order' => 1]);
    $product = ProductModel::create(['type' => 'single', 'name' => 'Top Teste', 'slug' => 'top-teste']);

    $this->variant = ProductVariantModel::create([
        'product_id' => $product->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'sku' => 'SKU-001',
        'price_cents' => 9900,
    ]);

    $this->user = User::factory()->create();
});

it('happy path: responds 201 in the standard envelope and available stock reflects the entry', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/catalog/stock-entries', [
        'variant_id' => $this->variant->id,
        'quantity' => 10,
        'reason' => 'entrada inicial',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['movement_id', 'available_after']])
        ->assertJsonPath('data.available_after', 10);
});

it('returns 404 translated by the central handler when variant does not exist', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/catalog/stock-entries', [
        'variant_id' => 999999,
        'quantity' => 5,
    ]);

    $response->assertStatus(404)
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonPath('error.code', 'variant_not_found');
});

it('returns 422 with a PT-BR message when quantity is zero or negative', function (int $quantity): void {
    $response = $this->actingAs($this->user)->postJson('/api/v1/catalog/stock-entries', [
        'variant_id' => $this->variant->id,
        'quantity' => $quantity,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error')
        ->assertJsonPath('error.details.quantity.0', 'A quantidade mínima é 1.');
})->with([0, -1, -10]);

it('returns 401 when unauthenticated', function (): void {
    $this->postJson('/api/v1/catalog/stock-entries', [
        'variant_id' => $this->variant->id,
        'quantity' => 5,
    ])->assertStatus(401);
});
