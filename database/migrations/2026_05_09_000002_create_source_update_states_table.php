<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_update_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('success');
            $table->boolean('rerun')->default(false);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('last_error')->nullable();
            $table->text('last_output')->nullable();
            $table->string('last_log_path')->nullable();
            $table->string('worker_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_update_states');
    }
};
