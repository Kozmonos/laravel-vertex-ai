<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_vertex_batch_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('vertex_job_name')->nullable();
            $table->string('vertex_state')->nullable();
            $table->unsignedInteger('succeeded_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('polled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_vertex_batch_jobs');
    }
};
