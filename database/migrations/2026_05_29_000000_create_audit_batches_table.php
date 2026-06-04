<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('context')->index();       // AuditBatchContext
            $table->string('description');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('finished')->default(0);
            $table->string('status', 32)->default('pending')->index();
            $table->string('archive_path')->nullable();   // where the JSONL log landed
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_batches');
    }
};
