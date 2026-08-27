<?php

namespace Tests\Unit\Services;

use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\ValidasiBukti;
use App\Services\ValidasiBuktiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ValidasiBuktiServiceTest extends TestCase
{
    use RefreshDatabase;

    private ValidasiBuktiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ValidasiBuktiService();
    }

    public function test_get_form_data_returns_sopirs_and_tujuans(): void
    {
        Sopir::create(['nama' => 'Budi']);
        Sopir::create(['nama' => 'Agus']);
        Tujuan::create(['nama' => 'Jombang']);
        Tujuan::create(['nama' => 'Kediri']);

        $result = $this->service->getFormData();

        $this->assertArrayHasKey('sopirs', $result);
        $this->assertArrayHasKey('tujuans', $result);
        $this->assertCount(2, $result['sopirs']);
        $this->assertCount(2, $result['tujuans']);
    }

    public function test_setujui_creates_new_sopir_when_sopir_baru(): void
    {
        $item = ValidasiBukti::create([
            'nama_sopir' => 'Sopir Baru',
            'sopir_baru' => true,
            'nama_tujuan' => 'Jombang',
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);

        $request = new Request(['catatan_mitra' => 'OK']);
        $this->service->setujui($request, $item->id);

        $this->assertDatabaseHas('sopirs', ['nama' => 'Sopir Baru', 'status' => 'aktif']);
        $item->refresh();
        $this->assertEquals('disetujui', $item->status);
        $this->assertNotNull($item->kode_sopir);
    }

    public function test_setujui_creates_new_tujuan_when_tujuan_baru(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi']);

        $item = ValidasiBukti::create([
            'nama_sopir' => 'Budi',
            'kode_sopir' => $sopir->kode_sopir,
            'nama_tujuan' => 'Tujuan Baru',
            'tujuan_baru' => true,
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);

        $request = new Request(['catatan_mitra' => 'OK']);
        $this->service->setujui($request, $item->id);

        $this->assertDatabaseHas('tujuans', ['nama' => 'Tujuan Baru', 'status' => 'aktif']);
        $item->refresh();
        $this->assertNotNull($item->kode_tujuan);
    }

    public function test_tolak_updates_status_to_ditolak(): void
    {
        $item = ValidasiBukti::create([
            'nama_sopir' => 'Budi',
            'nama_tujuan' => 'Jombang',
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);

        $request = new Request(['catatan_mitra' => 'Foto tidak jelas']);
        $this->service->tolak($request, $item->id);

        $item->refresh();
        $this->assertEquals('ditolak', $item->status);
        $this->assertEquals('Foto tidak jelas', $item->catatan_mitra);
    }

    public function test_get_kelola_data_returns_filtered_results(): void
    {
        ValidasiBukti::create([
            'nama_sopir' => 'Budi',
            'nama_tujuan' => 'Jombang',
            'foto' => 'bukti/a.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);
        ValidasiBukti::create([
            'nama_sopir' => 'Agus',
            'nama_tujuan' => 'Kediri',
            'foto' => 'bukti/b.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-23',
            'status' => 'disetujui',
        ]);

        $request = new Request(['status' => 'pending', 'search' => '']);
        $result = $this->service->getKelolaData($request);

        $this->assertCount(1, $result['list']);
        $this->assertEquals('pending', $result['status']);
    }

    public function test_get_detail_data_returns_item_with_relations(): void
    {
        $item = ValidasiBukti::create([
            'nama_sopir' => 'Budi',
            'nama_tujuan' => 'Jombang',
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);

        $result = $this->service->getDetailData($item->id);

        $this->assertArrayHasKey('item', $result);
        $this->assertArrayHasKey('sopirs', $result);
        $this->assertArrayHasKey('tujuans', $result);
    }
}
