<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Ricki Irsa Mahendra',
            'email'    => 'mitra@armada.id',
            'password' => Hash::make('mitra123'),
            'phone'    => '081298765432',
        ]);

        User::create([
            'name'     => 'Haryanto',
            'email'    => 'haryanto@armada.id',
            'password' => Hash::make('mitra123'),
            'phone'    => '081211112222',
        ]);
    }
}
