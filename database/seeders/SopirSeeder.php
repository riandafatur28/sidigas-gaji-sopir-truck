<?php

namespace Database\Seeders;

use App\Models\Sopir;
use Illuminate\Database\Seeder;

class SopirSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Riki', 'Kola', 'Firsa', 'Wahyu', 'Ginem',
            'POR', 'Didik', 'Yuri', 'Agung',
            'Gun', 'Anjar', 'Wilujeng', 'Yanto', 'Soim',
            'Kuwat', 'Toni', 'Aripin', 'Avit', 'Radib',
            'Topik', 'Narji', 'Eka Bence', 'Prapto', 'Berok',
            'Manto', 'Eko Wilangan', 'Torik', 'Adib', 'Wakub',
            'Bondan', 'CMM', 'Sutrisno', 'Slamet', 'Supri',
            'Karjo', 'Parno', 'Tumijan', 'Sujarwo', 'Budi',
            'Heri', 'Joko', 'Sugeng', 'Agus', 'Rudi',
            'Mujiono', 'Warsito', 'Kusnan', 'Rohmad', 'Samsul',
        ];

        foreach ($names as $nama) {
            Sopir::create([
                'nama'   => $nama,
                'status' => 'aktif',
            ]);
        }

        $this->command->info('Seeded ' . count($names) . ' sopirs.');
    }
}
