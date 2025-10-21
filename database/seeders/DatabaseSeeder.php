<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

          User::where('username', 'admin')->orWhere('email', 'admin@admin.com')->delete();

        // CREATE NEW ADMIN USER
        User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password' => Hash::make('123456'),
        ]);

         User::where('username', 'user1')->orWhere('email', 'user1@user.com')->delete();
        // OPTIONAL: CREATE USER #1 IF IT SHOULD ALWAYS EXIST
        User::Create(
            [
                'username' => 'user1',
                'name' => 'User One',
                'email' => 'user1@user.com',
                'password' => Hash::make('123456'),
            ]
        );
    }
}
