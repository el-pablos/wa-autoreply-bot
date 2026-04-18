<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('escalation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('from_number');
            $table->string('trigger_reason');
            $table->string('escalated_to');
            $table->text('message_snippet');
            $table->timestamp('escalated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_logs');
    }
};
