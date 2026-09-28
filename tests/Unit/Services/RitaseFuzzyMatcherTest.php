<?php

namespace Tests\Unit\Services;

use App\Models\Sopir;
use App\Services\RitaseFuzzyMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RitaseFuzzyMatcherTest extends TestCase
{
    use RefreshDatabase;

    private RitaseFuzzyMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new RitaseFuzzyMatcher();

        Sopir::create(['kode_sopir' => 'SPR-001', 'nama' => 'Aripin', 'status' => 'aktif']);
        Sopir::create(['kode_sopir' => 'SPR-002', 'nama' => 'Topik', 'status' => 'aktif']);
        Sopir::create(['kode_sopir' => 'SPR-003', 'nama' => 'Santoso', 'status' => 'aktif']);
        Sopir::create(['kode_sopir' => 'SPR-004', 'nama' => 'Gun', 'status' => 'aktif']);
    }

    private function matchSingle(string $name): array
    {
        $results = $this->matcher->matchDrivers([$name]);
        $this->assertCount(1, $results);
        return $results[0];
    }

    public function test_ari_tidak_match_ke_aripin_beda_orang(): void
    {
        $result = $this->matchSingle('Ari');

        $this->assertFalse($result['matched'], "'Ari' tidak boleh match ke 'Aripin'");
        $this->assertSame(0.0, $result['confidence']);
    }

    public function test_aripin_exact_match(): void
    {
        $result = $this->matchSingle('Aripin');

        $this->assertTrue($result['matched']);
        $this->assertSame('SPR-001', $result['sopir']->kode_sopir);
        $this->assertSame(100.0, $result['confidence']);
    }

    public function test_typo_satu_huruf_nama_panjang_tetap_match(): void
    {
        $result = $this->matchSingle('Aripn');

        $this->assertTrue($result['matched']);
        $this->assertSame('SPR-001', $result['sopir']->kode_sopir);
    }

    public function test_topa_tidak_match_ke_topik(): void
    {
        $result = $this->matchSingle('Topa');

        $this->assertFalse($result['matched'], "'Topa' tidak boleh match ke 'Topik'");
    }

    public function test_typo_nama_panjang_tetap_match(): void
    {
        $result = $this->matchSingle('Santsos');

        $this->assertTrue($result['matched']);
        $this->assertSame('SPR-003', $result['sopir']->kode_sopir);
    }

    public function test_nama_pendek_exact_tetap_match(): void
    {
        $result = $this->matchSingle('Gun');

        $this->assertTrue($result['matched']);
        $this->assertSame('SPR-004', $result['sopir']->kode_sopir);
    }
}
