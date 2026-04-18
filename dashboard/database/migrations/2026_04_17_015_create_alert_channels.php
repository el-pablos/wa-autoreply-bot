<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alert_channels', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['wa', 'email']);
            $table->string('target');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_alert_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active'], 'idx_alert_channels_type_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_channels');
    }
};
