<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['owner', 'admin', 'viewer'])->default('owner')->after('password');
            }

            if (!Schema::hasColumn('users', 'totp_secret')) {
                $table->text('totp_secret')->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('totp_secret');
            }

            if (!Schema::hasColumn('users', 'backup_codes')) {
                $table->json('backup_codes')->nullable()->after('two_factor_enabled');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('backup_codes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'role',
                'totp_secret',
                'two_factor_enabled',
                'backup_codes',
                'last_login_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
