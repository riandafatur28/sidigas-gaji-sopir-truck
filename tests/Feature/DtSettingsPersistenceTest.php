<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Periode;
use App\Models\Penggajian;
use App\Models\Ritase;
use App\Models\Setting;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\User;
use App\Services\DtConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DtSettingsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::create([
            'name' => 'Super',
            'email' => 'super@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);
    }

    public function test_dt_nominal_survives_cache_flush(): void
    {
        Setting::setValue('dt_nominal', 400000);
        $this->assertSame(400000, DtConfig::nominal());

        Cache::flush();

        $this->assertSame(400000, DtConfig::nominal());
        $this->assertSame(400000, Setting::getValue('dt_nominal'));
    }

    public function test_dt_single_regencies_survive_cache_flush(): void
    {
        Setting::setValue('dt_single_regencies', ['Nganjuk', 'Blitar']);
        Cache::flush();

        $this->assertSame(['Nganjuk', 'Blitar'], DtConfig::singleRegencies());
    }

    public function test_toggle_validasi_survives_cache_flush(): void
    {
        Setting::setValue('aturan_validasi_enabled', true);
        Cache::flush();

        $this->assertTrue(Setting::getValue('aturan_validasi_enabled', false));
    }

    public function test_update_nominal_syncs_active_periode_immediately(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Periode Aktif Tes',
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'status' => 'aktif',
        ]);
        $sopir = Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        $tujuan = Tujuan::create(['nama' => 'Nganjuk', 'status' => 'aktif']);
        $ritase = Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => now()->toDateString(),
            'waktu' => 'pagi',
            'kabupaten' => 'Nganjuk',
            'status' => 'valid',
            'dt' => 330000,
        ]);
        Penggajian::create([
            'periode_id' => $periode->id,
            'tujuan_id' => $tujuan->id,
            'kode_sopir' => $sopir->kode_sopir,
            'tanggal' => now()->toDateString(),
            'dt' => 330000,
            'total' => 330000,
        ]);

        $this->actingAs($this->superadmin());
        $this->put('/settings/dt-nominal', ['nominal' => 400000])->assertRedirect();

        $this->assertSame(400000, (int) $ritase->fresh()->dt);
        $this->assertSame(400000, (int) Penggajian::first()->dt);
        $this->assertSame(400000, Setting::getValue('dt_nominal'));

        Cache::flush();
        $this->assertSame(400000, DtConfig::nominal());
    }

    public function test_update_nominal_persists_when_no_active_periode(): void
    {
        $this->actingAs($this->superadmin());
        $this->put('/settings/dt-nominal', ['nominal' => 400000])->assertRedirect();

        $this->assertSame(400000, Setting::getValue('dt_nominal'));
        Cache::flush();
        $this->assertSame(400000, DtConfig::nominal());
    }
}
