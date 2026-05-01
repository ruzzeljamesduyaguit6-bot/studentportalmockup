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
        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'units')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unsignedTinyInteger('units')->default(3)->after('name');
            });
        }

        if (!Schema::hasTable('course_subject')) {
            Schema::create('course_subject', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['course_id', 'subject_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('course_subject')) {
            Schema::drop('course_subject');
        }

        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'units')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('units');
            });
        }
    }
};
