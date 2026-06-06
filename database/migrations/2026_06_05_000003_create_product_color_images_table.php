<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_color_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('color_id')->constrained('colors')->cascadeOnDelete();
            $table->string('storage_key', 500);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'color_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_color_images');
    }
};
