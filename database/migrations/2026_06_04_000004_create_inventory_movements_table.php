<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->enum('type', ['entrada', 'saida', 'reserva', 'liberacao', 'ajuste']);
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('parent_movement_id')
                ->nullable()
                ->constrained('inventory_movements');
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['variant_id', 'type']);
            $table->index(['type', 'expires_at']);
            $table->index('parent_movement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
