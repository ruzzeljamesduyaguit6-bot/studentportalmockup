<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'user_type' => 'admin',
            'user_code' => 'A6969',
        ]);

        // Create a regular user
        User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'user_type' => 'student',
        ]);

        // Create a professor user
        User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'user_type' => 'professor',
        ]);

        // Create additional test users
        User::factory(5)->create();
    }
}
