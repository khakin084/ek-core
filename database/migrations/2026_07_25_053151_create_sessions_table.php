<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Session ID
            $table->foreignId('user_id')->nullable()->index(); // Associated user
            $table->string('ip_address', 45)->nullable(); // Client IP
            $table->text('user_agent')->nullable(); // Browser info
            $table->text('payload'); // Session data (serialized)
            $table->integer('last_activity')->index(); // Last activity timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};