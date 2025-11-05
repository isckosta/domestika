<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@domestika.local',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        // Create regular user (contractor)
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@domestika.local',
            'password' => Hash::make('user123'),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('contractor');

        // Create moderator user
        $moderator = User::create([
            'name' => 'Moderator User',
            'email' => 'moderator@domestika.local',
            'password' => Hash::make('moderator123'),
            'email_verified_at' => now(),
        ]);

        $moderator->assignRole('moderator');
    }
}
