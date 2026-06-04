<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->string('actor')->index();        // e.g. "admin:3", "system:webhook"
            $table->string('subject')->index();       // e.g. "order:1042", "variant:88"
            $table->string('severity', 16)->default('info');
            $table->json('context')->nullable();
            $table->foreignId('batch_id')->nullable()->index();
            $table->timestamp('occurred_at')->index();

            // Common lookup: everything that happened to one subject, newest first.
            $table->index(['subject', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
