<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration_id')->unique();
            $table->string('from_version')->nullable();
            $table->string('to_version')->nullable();
            $table->string('status')->default('pending'); // pending|running|success|failed|rolled_back
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('rollbackable')->default(false);
            $table->text('error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('from_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_migrations');
    }
};