<?php

namespace Tests\Unit\Models;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_active_status_activates_current_periode(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => Carbon::today()->subDays(3),
            'tanggal_selesai' => Carbon::today()->addDays(3),
            'status' => 'selesai',
        ]);

        $result = Periode::syncActiveStatus();

        $this->assertNotNull($result);
        $this->assertEquals($periode->id, $result->id);
        $periode->refresh();
        $this->assertEquals('aktif', $periode->status);
    }

    public function test_sync_active_status_deactivates_past_periode(): void
    {
        $past = Periode::create([
            'nama_periode' => 'Past',
            'tanggal_mulai' => Carbon::today()->subDays(10),
            'tanggal_selesai' => Carbon::today()->subDays(5),
            'status' => 'aktif',
        ]);

        Periode::syncActiveStatus();

        $past->refresh();
        $this->assertEquals('selesai', $past->status);
    }

    public function test_sync_active_status_returns_null_when_no_current(): void
    {
        Periode::create([
            'nama_periode' => 'Past',
            'tanggal_mulai' => Carbon::today()->subDays(10),
            'tanggal_selesai' => Carbon::today()->subDays(5),
            'status' => 'aktif',
        ]);

        $result = Periode::syncActiveStatus();

        $this->assertNull($result);
    }

    public function test_scope_aktif_filters_active(): void
    {
        // P1 spans today → syncActiveStatus will keep it 'aktif'
        $p1 = Periode::create(['nama_periode' => 'P1', 'tanggal_mulai' => Carbon::today()->subDays(3), 'tanggal_selesai' => Carbon::today()->addDays(3), 'status' => 'selesai']);
        // P2 is in the far future → syncActiveStatus will mark it 'selesai'
        $p2 = Periode::create(['nama_periode' => 'P2', 'tanggal_mulai' => Carbon::today()->addDays(60), 'tanggal_selesai' => Carbon::today()->addDays(90), 'status' => 'aktif']);

        // syncActiveStatus activates the one spanning today
        Periode::syncActiveStatus();

        $aktif = Periode::aktif()->get();

        $this->assertCount(1, $aktif);
        $this->assertEquals($p1->id, $aktif->first()->id);
    }

    public function test_scope_aktif_only_returns_active_periodes(): void
    {
        // P1 spans today → syncActiveStatus will keep it 'aktif'
        $p1 = Periode::create(['nama_periode' => 'Active', 'tanggal_mulai' => Carbon::today()->subDays(3), 'tanggal_selesai' => Carbon::today()->addDays(3), 'status' => 'selesai']);
        // P2 is far past → syncActiveStatus will mark it 'selesai'
        $p2 = Periode::create(['nama_periode' => 'Inactive', 'tanggal_mulai' => Carbon::today()->subDays(20), 'tanggal_selesai' => Carbon::today()->subDays(10), 'status' => 'aktif']);

        Periode::syncActiveStatus();

        $ids = Periode::aktif()->pluck('id')->toArray();

        $this->assertContains($p1->id, $ids);
        $this->assertNotContains($p2->id, $ids);
    }
}
