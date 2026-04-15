<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('message_logs') || !Schema::hasColumn('message_logs', 'from_number')) {
            return;
        }

        $column = DB::table('information_schema.COLUMNS')
            ->select('CHARACTER_MAXIMUM_LENGTH as max_length')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'message_logs')
            ->where('COLUMN_NAME', 'from_number')
            ->first();

        if ((int) ($column->max_length ?? 0) >= 64) {
            return;
        }

        DB::statement("ALTER TABLE `message_logs` MODIFY `from_number` VARCHAR(64) NOT NULL COMMENT 'Nomor pengirim'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('message_logs') || !Schema::hasColumn('message_logs', 'from_number')) {
            return;
        }

        $column = DB::table('information_schema.COLUMNS')
            ->select('CHARACTER_MAXIMUM_LENGTH as max_length')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'message_logs')
            ->where('COLUMN_NAME', 'from_number')
            ->first();

        if ((int) ($column->max_length ?? 0) <= 20) {
            return;
        }

        DB::statement("ALTER TABLE `message_logs` MODIFY `from_number` VARCHAR(20) NOT NULL COMMENT 'Nomor pengirim'");
    }
};
