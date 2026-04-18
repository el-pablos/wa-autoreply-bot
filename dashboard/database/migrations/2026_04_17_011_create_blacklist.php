<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 64)->unique();
            $table->text('reason')->nullable();
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamp('unblock_at')->nullable();
            $table->string('blocked_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'blocked_at'], 'idx_blacklist_active_blocked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist');
    }
};
