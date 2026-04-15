<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 20)->comment('Nomor pengirim');
            $table->text('message_text')->nullable()->comment('Isi pesan');
            $table->string('message_type', 30)->default('text')->comment('Tipe pesan');
            $table->boolean('is_allowed')->default(false)->comment('Ada di allow-list?');
            $table->boolean('replied')->default(false)->comment('Sudah dibalas bot?');
            $table->text('reply_text')->nullable()->comment('Teks balasan bot');
            $table->string('group_id', 50)->nullable()->comment('ID grup jika dari grup');
            $table->timestamp('received_at')->useCurrent();
            $table->index('from_number');
            $table->index('received_at');
            $table->index('is_allowed');
            $table->index('replied');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
