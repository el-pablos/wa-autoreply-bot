<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_daily_summary', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('messages_in')->default(0);
            $table->unsignedInteger('messages_out')->default(0);
            $table->unsignedInteger('avg_response_ms')->default(0);
            $table->json('top_numbers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_summary');
    }
};
