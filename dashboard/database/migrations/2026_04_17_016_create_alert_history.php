<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('alert_channels')->cascadeOnDelete();
            $table->enum('severity', ['info', 'warn', 'err']);
            $table->text('message');
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamps();

            $table->index(['severity', 'created_at'], 'idx_alert_history_severity_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_history');
    }
};
