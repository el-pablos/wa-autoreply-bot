<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rate_limit_violations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->timestamp('window_start');
            $table->integer('message_count');
            $table->timestamps();

            $table->index(['phone_number', 'window_start'], 'idx_rlv_phone_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_violations');
    }
};
