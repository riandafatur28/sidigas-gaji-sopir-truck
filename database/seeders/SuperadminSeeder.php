<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'senjanugraha320@gmail.com'],
            [
                'name' => 'Ricki Irsa Mahendra',
                'password' => Hash::make('superadmin123'),
                'role' => 'superadmin',
            ]
        );
    }
}
