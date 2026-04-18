<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_conversation_history', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 64);
            $table->enum('role', ['system', 'user', 'assistant']);
            $table->text('content');
            $table->unsignedInteger('tokens')->nullable();
            $table->timestamps();

            $table->index(['phone_number', 'created_at'], 'idx_ai_history_phone_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_history');
    }
};
