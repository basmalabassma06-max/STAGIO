<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'besmalasegueni3@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('besmala2005'),
                'role' => 'admin',
                'is_active' => 1,
                'status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
    }
}