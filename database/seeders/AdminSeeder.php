<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'infoperforma.lk@gmail.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );
    }
}
