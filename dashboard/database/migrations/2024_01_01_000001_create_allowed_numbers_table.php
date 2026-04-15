<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('allowed_numbers')) {
            return;
        }

        Schema::create('allowed_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique()->comment('Format: 628xxx');
            $table->string('label', 100)->nullable()->comment('Nama/catatan opsional');
            $table->boolean('is_active')->default(true)->comment('1=aktif, 0=nonaktif');
            $table->timestamps();
            $table->index(['phone_number', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_numbers');
    }
};
