<?php

namespace Database\Seeders;

use App\Models\Tujuan;
use Illuminate\Database\Seeder;

class TujuanSeeder extends Seeder
{
    public function run(): void
    {
        $tujuans = [
            ['nama' => 'Blitar Kota',      'status' => 'aktif'],
            ['nama' => 'Pare Kota',         'status' => 'aktif'],
            ['nama' => 'Watualang Ngawi',   'status' => 'aktif'],
            ['nama' => 'Nganjuk Kota',      'status' => 'aktif'],
            ['nama' => 'Kediri Kota',       'status' => 'aktif'],
            ['nama' => 'Jombang Kota',      'status' => 'aktif'],
            ['nama' => 'Patching Pare',     'status' => 'aktif'],
            ['nama' => 'CMM Blitar',        'status' => 'aktif'],
            ['nama' => 'Bondan Patching',   'status' => 'aktif'],
            ['nama' => 'Wilangan',          'status' => 'aktif'],
            ['nama' => 'Madiun',            'status' => 'aktif'],
            ['nama' => 'Caruban',           'status' => 'aktif'],
            ['nama' => 'Malang',            'status' => 'aktif'],
            ['nama' => 'Surabaya',          'status' => 'aktif'],
            ['nama' => 'Ngawi Kota',        'status' => 'aktif'],
        ];

        foreach ($tujuans as $data) {
            Tujuan::create($data);
        }

        $this->command->info('Seeded ' . count($tujuans) . ' tujuans.');
    }
}
