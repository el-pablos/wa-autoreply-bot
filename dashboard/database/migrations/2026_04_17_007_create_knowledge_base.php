<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->json('keywords')->nullable();
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('match_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'match_count'], 'idx_kb_active_match_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base');
    }
};
