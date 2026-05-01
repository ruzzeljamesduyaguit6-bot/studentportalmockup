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
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code')->nullable()->unique()->after('user_type');
        });

        $year = (string) now()->year;
        $roles = [
            'student' => 'S',
            'professor' => 'P',
        ];

        foreach ($roles as $role => $prefix) {
            $users = DB::table('users')
                ->where('user_type', $role)
                ->orderBy('id')
                ->get(['id']);

            $counter = 1;
            foreach ($users as $user) {
                $code = sprintf('%s%s %05d', $prefix, $year, $counter);
                DB::table('users')->where('id', $user->id)->update(['user_code' => $code]);
                $counter++;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['user_code']);
            $table->dropColumn('user_code');
        });
    }
};
