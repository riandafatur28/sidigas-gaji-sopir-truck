<?php

namespace Tests\Unit\Models;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SopirTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_aktif_filters_active(): void
    {
        Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        Sopir::create(['nama' => 'Agus', 'status' => 'nonaktif']);

        $aktif = Sopir::aktif()->get();

        $this->assertCount(1, $aktif);
        $this->assertEquals('Budi', $aktif->first()->nama);
    }

    public function test_scope_nonaktif_filters_inactive(): void
    {
        Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        Sopir::create(['nama' => 'Agus', 'status' => 'nonaktif']);

        $nonaktif = Sopir::nonaktif()->get();

        $this->assertCount(1, $nonaktif);
        $this->assertEquals('Agus', $nonaktif->first()->nama);
    }

    public function test_sync_active_status_activates_sopir_with_ritase_in_active_periode(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_selesai' => now()->addDays(3),
            'status' => 'aktif',
        ]);

        $sopir = Sopir::create(['nama' => 'Budi', 'status' => 'nonaktif']);
        $tujuan = Tujuan::create(['nama' => 'Jombang']);

        Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => now(),
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        Sopir::syncActiveStatus();

        $sopir->refresh();
        $this->assertEquals('aktif', $sopir->status);
    }

    public function test_sync_active_status_deactivates_sopir_without_ritase(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_selesai' => now()->addDays(3),
            'status' => 'aktif',
        ]);

        $sopirWithRitase = Sopir::create(['nama' => 'Budi', 'status' => 'nonaktif']);
        $sopirWithoutRitase = Sopir::create(['nama' => 'Agus', 'status' => 'aktif']);
        $tujuan = Tujuan::create(['nama' => 'Jombang']);

        Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopirWithRitase->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => now(),
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        Sopir::syncActiveStatus();

        $sopirWithRitase->refresh();
        $sopirWithoutRitase->refresh();

        $this->assertEquals('aktif', $sopirWithRitase->status);
        $this->assertEquals('nonaktif', $sopirWithoutRitase->status);
    }

    public function test_sync_active_status_deactivates_all_when_no_active_periode(): void
    {
        Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        Sopir::create(['nama' => 'Agus', 'status' => 'aktif']);

        Sopir::syncActiveStatus();

        $this->assertEquals(0, Sopir::where('status', 'aktif')->count());
    }
}
