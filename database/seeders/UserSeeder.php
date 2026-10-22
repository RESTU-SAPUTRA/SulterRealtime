<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'operator@campus.ac.id'],
            [
                'name'     => 'Operator Shuttle Kampus',
                'password' => Hash::make('password123'),
                'role'     => 'operator',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@campus.ac.id'],
            [
                'name'     => 'Admin Utama Kampus',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]
        );
    }
}
