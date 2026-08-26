<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi validasi umum untuk seluruh aplikasi.
    | Digunakan oleh Form Request classes dan controllers.
    |
    */

    // Aturan validasi umum
    'rules' => [
        'kode' => 'required|string|max:20',
        'nama' => 'required|string|min:3|max:255',
        'tanggal' => 'required|date',
        'waktu' => 'required|in:pagi,malam',
        'status' => 'required|in:valid,pending,gagal_produksi',
        'kabupaten' => 'required|string|max:100',
        'catatan' => 'nullable|string|max:500',
        'nominal' => 'required|numeric|min:0',
        'periode_id' => 'required|exists:periodes,id',
        'kode_sopir' => 'required|exists:sopirs,kode_sopir',
        'kode_tujuan' => 'required|exists:tujuans,kode_tujuan',
    ],

    // Pesan error umum dalam Bahasa Indonesia
    'messages' => [
        'required' => ':attribute wajib diisi.',
        'string' => ':attribute harus berupa teks.',
        'numeric' => ':attribute harus berupa angka.',
        'min' => ':attribute minimal :min karakter.',
        'max' => ':attribute maksimal :max karakter.',
        'in' => ':attribute harus salah satu dari: :values.',
        'date' => ':attribute harus berupa tanggal yang valid.',
        'exists' => ':attribute tidak ditemukan dalam sistem.',
        'min_num' => ':attribute minimal harus :min.',
    ],

    // Nama attribute yang ditampilkan di pesan error
    'attributes' => [
        'kode_sopir' => 'Kode Sopir',
        'kode_tujuan' => 'Kode Tujuan',
        'kode_ritase' => 'Kode Ritase',
        'kode_periode' => 'Kode Periode',
        'nama' => 'Nama',
        'tanggal' => 'Tanggal',
        'waktu' => 'Waktu',
        'status' => 'Status',
        'kabupaten' => 'Kabupaten',
        'catatan' => 'Catatan',
        'nominal_kompensasi' => 'Nominal Kompensasi',
        'upah_sopir' => 'Upah Sopir',
        'uang_solar' => 'Uang Solar',
        'dt' => 'DT',
        'tol' => 'Tol',
        'periode_id' => 'Periode',
        'bbm_per_rit' => 'BBM per Rit',
        'upah_per_rit' => 'Upah per Rit',
        'tol_per_rit' => 'Tol per Rit',
    ],

];
