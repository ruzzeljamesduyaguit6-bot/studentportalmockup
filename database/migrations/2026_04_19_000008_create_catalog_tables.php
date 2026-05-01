<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $defaultDesignations = [
            'Instructor I',
            'Instructor II',
            'Assistant Professor',
            'Associate Professor',
            'Professor',
        ];

        $defaultDepartments = [
            'College of Computer Studies',
            'College of Engineering',
            'College of Business',
            'College of Education',
        ];

        $defaultCourses = [
            'BS Information Systems',
            'BS Computer Science',
            'BS Information Technology',
            'BS Business Administration',
        ];

        $this->insertUniqueNames('designations', $defaultDesignations);
        $this->insertUniqueNames('departments', $defaultDepartments);
        $this->insertUniqueNames('courses', $defaultCourses);

        $this->insertDistinctFromUsers('designation', 'designations');
        $this->insertDistinctFromUsers('department', 'departments');
        $this->insertDistinctFromUsers('course', 'courses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('designations');
    }

    private function insertDistinctFromUsers(string $column, string $table): void
    {
        $values = DB::table('users')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column)
            ->toArray();

        $this->insertUniqueNames($table, $values);
    }

    private function insertUniqueNames(string $table, array $values): void
    {
        foreach ($values as $value) {
            $name = trim((string) $value);
            if ($name === '') {
                continue;
            }

            $exists = DB::table($table)->where('name', $name)->exists();
            if ($exists) {
                continue;
            }

            DB::table($table)->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
