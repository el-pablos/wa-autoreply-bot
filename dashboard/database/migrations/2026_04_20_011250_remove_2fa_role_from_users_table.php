<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'totp_secret', 'two_factor_enabled', 'backup_codes']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer')->after('email');
            $table->string('totp_secret')->nullable()->after('role');
            $table->boolean('two_factor_enabled')->default(false)->after('totp_secret');
            $table->json('backup_codes')->nullable()->after('two_factor_enabled');
        });
    }
};
