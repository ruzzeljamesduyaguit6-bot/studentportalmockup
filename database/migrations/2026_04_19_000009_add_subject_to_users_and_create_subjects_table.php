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
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('users', 'subject')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('subject')->nullable()->after('course');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'subject')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('subject');
            });
        }

        if (Schema::hasTable('subjects')) {
            Schema::drop('subjects');
        }
    }
};
