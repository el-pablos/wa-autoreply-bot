<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bot_settings')) {
            return;
        }

        Schema::create('bot_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value');
            $table->string('description', 255)->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
