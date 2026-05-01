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
        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'department_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('departments')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'degree_level')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('degree_level', 40)->nullable()->after('department_id');
            });
        }

        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'course_code')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('course_code', 40)->nullable()->unique()->after('degree_level');
            });
        }

        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'total_years')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unsignedTinyInteger('total_years')->nullable()->after('course_code');
            });
        }

        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'subject_code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('subject_code', 40)->nullable()->unique()->after('name');
            });
        }

        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'department_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('units')
                    ->constrained('departments')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'is_free_for_all')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_free_for_all')->default(false)->after('department_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'is_free_for_all')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('is_free_for_all');
            });
        }

        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'department_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'subject_code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('subject_code');
            });
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'total_years')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('total_years');
            });
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'course_code')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('course_code');
            });
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'degree_level')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('degree_level');
            });
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'department_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }
    }
};
