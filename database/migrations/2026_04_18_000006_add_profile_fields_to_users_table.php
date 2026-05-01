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
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('name');
            $table->string('contact')->nullable()->after('birthday');
            $table->string('designation')->nullable()->after('contact');
            $table->string('department')->nullable()->after('designation');
            $table->string('course')->nullable()->after('department');
            $table->string('year_level')->nullable()->after('course');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'birthday',
                'contact',
                'designation',
                'department',
                'course',
                'year_level',
            ]);
        });
    }
};
