<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DT (Uang Transport) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk perhitungan DT (Uang Transport) sopir dump truck.
    | Nilai ini digunakan oleh RitaseService untuk menghitung DT per ritase.
    |
    */

    // Nilai DT per ritase (Rp)
    'value' => 330000,

    // Daftar kabupaten yang hanya mendapat 1 DT per hari per waktu
    // (sama kabupaten + sama waktu = DT ke-2 = 0)
    'single_dt_regencies' => [
        'Nganjuk',
        'Kediri',
        'Kota Kediri',
        'Jombang',
    ],

    // Potongan operasional per rit valid (Rp)
    'potongan_per_rit' => 20000,

];
