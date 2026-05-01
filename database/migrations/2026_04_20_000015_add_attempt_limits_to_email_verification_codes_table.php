<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('email_verification_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('email_verification_codes', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('code_hash');
            }

            if (!Schema::hasColumn('email_verification_codes', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_verification_codes', function (Blueprint $table) {
            if (Schema::hasColumn('email_verification_codes', 'locked_until')) {
                $table->dropColumn('locked_until');
            }

            if (Schema::hasColumn('email_verification_codes', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};
