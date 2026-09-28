<?php

namespace Tests\Unit\Services;

use App\Services\RitaseCreator;
use Tests\TestCase;

class RitaseCreatorTest extends TestCase
{
    private RitaseCreator $creator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creator = new RitaseCreator();
    }

    public function test_kabupaten_eksplisit_menang_atas_kecamatan(): void
    {
        // "kandangan" adalah kecamatan Kediri, tapi rute menyebut "ngawi"
        // secara eksplisit -> Ngawi yang dipakai.
        $this->assertSame(
            'Ngawi',
            $this->creator->guessKabupaten('Paket overlay kandangan ngawi')
        );
    }

    public function test_kecamatan_tanpa_kabupaten_eksplisit(): void
    {
        $this->assertSame('Kediri', $this->creator->guessKabupaten('Paket overlay kandangan'));
        $this->assertSame('Kediri', $this->creator->guessKabupaten('Pare'));
        $this->assertSame('Jombang', $this->creator->guessKabupaten('patching Ngoro Jombang'));
    }

    public function test_tanpa_petunjuk_lokasi_jadi_lainnya(): void
    {
        $this->assertSame('Lainnya', $this->creator->guessKabupaten('Paket overlay Kantor Pusat'));
    }

    public function test_desa_level_matching(): void
    {
        $this->assertSame('Ngawi', $this->creator->guessKabupaten('Paket overlay gendingan ngawi'));
        $this->assertSame('Ponorogo', $this->creator->guessKabupaten('Paket overlay ngrayun'));
        $this->assertSame('Trenggalek', $this->creator->guessKabupaten('Paket overlay mlinjon'));
    }

    public function test_kabupaten_eksplisit_langsung(): void
    {
        $this->assertSame('Blitar', $this->creator->guessKabupaten('patching Srengat blitar'));
        $this->assertSame('Ngawi', $this->creator->guessKabupaten('cmm kedungmiri ngawi'));
        $this->assertSame('Kediri', $this->creator->guessKabupaten('jembatan kaliombo kediri'));
    }
}
