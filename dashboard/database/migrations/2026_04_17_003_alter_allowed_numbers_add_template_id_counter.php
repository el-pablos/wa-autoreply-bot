<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('allowed_numbers', function (Blueprint $table) {
            if (!Schema::hasColumn('allowed_numbers', 'template_id')) {
                $table->foreignId('template_id')->nullable()->after('label')->constrained('reply_templates')->nullOnDelete();
            }

            if (!Schema::hasColumn('allowed_numbers', 'reply_count_today')) {
                $table->unsignedInteger('reply_count_today')->default(0)->after('is_active');
            }

            if (!Schema::hasColumn('allowed_numbers', 'last_reply_at')) {
                $table->timestamp('last_reply_at')->nullable()->after('reply_count_today');
            }
        });
    }

    public function down(): void
    {
        Schema::table('allowed_numbers', function (Blueprint $table) {
            if (Schema::hasColumn('allowed_numbers', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            }

            if (Schema::hasColumn('allowed_numbers', 'reply_count_today')) {
                $table->dropColumn('reply_count_today');
            }

            if (Schema::hasColumn('allowed_numbers', 'last_reply_at')) {
                $table->dropColumn('last_reply_at');
            }
        });
    }
};
