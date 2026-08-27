<?php

namespace Tests\Unit\Models;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TujuanTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_aktif_filters_active(): void
    {
        Tujuan::create(['nama' => 'Jombang', 'status' => 'aktif']);
        Tujuan::create(['nama' => 'Kediri', 'status' => 'nonaktif']);

        $aktif = Tujuan::aktif()->get();

        $this->assertCount(1, $aktif);
        $this->assertEquals('Jombang', $aktif->first()->nama);
    }

    public function test_scope_nonaktif_filters_inactive(): void
    {
        Tujuan::create(['nama' => 'Jombang', 'status' => 'aktif']);
        Tujuan::create(['nama' => 'Kediri', 'status' => 'nonaktif']);

        $nonaktif = Tujuan::nonaktif()->get();

        $this->assertCount(1, $nonaktif);
        $this->assertEquals('Kediri', $nonaktif->first()->nama);
    }

    public function test_sync_active_status_activates_tujuan_with_ritase_in_active_periode(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_selesai' => now()->addDays(3),
            'status' => 'aktif',
        ]);

        $sopir = Sopir::create(['nama' => 'Budi']);
        $tujuan = Tujuan::create(['nama' => 'Jombang', 'status' => 'nonaktif']);

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

        Tujuan::syncActiveStatus();

        $tujuan->refresh();
        $this->assertEquals('aktif', $tujuan->status);
    }

    public function test_sync_active_status_deactivates_all_when_no_active_periode(): void
    {
        Tujuan::create(['nama' => 'Jombang', 'status' => 'aktif']);
        Tujuan::create(['nama' => 'Kediri', 'status' => 'aktif']);

        Tujuan::syncActiveStatus();

        $this->assertEquals(0, Tujuan::where('status', 'aktif')->count());
    }
}
