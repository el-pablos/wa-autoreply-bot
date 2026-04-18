<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key_hash', 128)->unique();
            $table->json('scopes')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['revoked_at', 'last_used_at'], 'idx_api_keys_revoked_last_used');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
