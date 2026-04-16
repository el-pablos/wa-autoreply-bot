<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('approved_sessions')) {
            return;
        }

        Schema::create('approved_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->timestamp('approved_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->string('approved_by', 20);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['phone_number', 'is_active']);
            $table->index('expires_at');
            $table->index('is_active');
            $table->index('approved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approved_sessions');
    }
};
