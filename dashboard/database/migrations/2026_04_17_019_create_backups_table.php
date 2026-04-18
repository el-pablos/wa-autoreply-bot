<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->enum('type', ['db', 'session']);
            $table->string('checksum', 128);
            $table->timestamps();

            $table->index(['type', 'created_at'], 'idx_backups_type_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
