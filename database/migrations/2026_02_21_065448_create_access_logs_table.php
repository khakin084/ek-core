<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action
            $table->foreignId('user_id')->nullable();

            // Module grouping (e.g. Item Master, Sales, Users)
            $table->string('module')->index();

            // Short readable description
            $table->string('description');

            // What model was affected
            $table->string('model_type')->nullable()->index();
            $table->unsignedBigInteger('model_id')->nullable()->index();

            // Event type (created, updated, deleted)
            $table->string('event')->nullable()->index();

            // Raw request payload (sanitized)
            $table->json('payload')->nullable();

            // Field-level changes
            $table->json('changes')->nullable();

            // Request metadata
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // Composite index for faster lookups per model
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};