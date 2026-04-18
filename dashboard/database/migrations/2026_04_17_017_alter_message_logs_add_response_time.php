<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('message_logs', 'response_time_ms')) {
                $table->unsignedInteger('response_time_ms')->nullable()->after('replied');
                $table->index(['response_time_ms', 'received_at'], 'idx_message_logs_response_received');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            if (Schema::hasColumn('message_logs', 'response_time_ms')) {
                $table->dropIndex('idx_message_logs_response_received');
                $table->dropColumn('response_time_ms');
            }
        });
    }
};
