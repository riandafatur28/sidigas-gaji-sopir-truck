<?php

namespace Tests\Unit\Services;

use App\Services\RitaseTextParser;
use Tests\TestCase;

class RitaseTextParserLemburTest extends TestCase
{
    private RitaseTextParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RitaseTextParser();
    }

    private function novemberFourthText(): string
    {
        return <<<'TXT'
        4 11 26 rabu
        Radib patching Pogalan trenggalek
        Eka bence patching gendingan ngawi
        Paket candirejo nganjuk
        1. Mbah POR
        2. Maji
        3. Gok to
        4. Soim
        5. Toni
        6. Anjar
        7. Narji
        8. Kola
        9. Gun
        10. Riki
        11. Yanto
        12. Topik
        13. Berok
        14. Hadi
        15. Agung
        16. Yuri
        17. Bondan
        18. Nur fahmi
        19. Firsa
        20. Ari
        21. Didik
        lembur paket Candirejo nganjuk
        1.Gun
        2.Toni
        3.Berok
        4.Gok to
        5.Topik
        6.Kola
        7. Mbah POR
        8. Firsa
        9. Agung
        10. Nur fahmi
        11. Hadi
        12. Bondan
        13. Yuri
        TXT;
    }

    public function test_lembur_section_does_not_create_a_new_package(): void
    {
        $result = $this->parser->parse($this->novemberFourthText());

        $this->assertSame('2026-11-04', $result['date']);
        // Pogalan, Gendingan, Candirejo -- no fourth "lembur" package.
        $this->assertCount(3, $result['packages']);
        $this->assertSame([], $result['lembur_unmatched'] ?? []);
    }

    public function test_lembur_drivers_are_attached_to_the_base_package_only(): void
    {
        $result = $this->parser->parse($this->novemberFourthText());

        $candirejo = collect($result['packages'])->firstWhere('route_name', 'Paket candirejo nganjuk');
        $this->assertNotNull($candirejo);
        $this->assertCount(21, $candirejo['drivers']);
        $this->assertCount(13, $candirejo['lembur_drivers'] ?? []);
        $this->assertContains('Gun', $candirejo['lembur_drivers']);
        $this->assertContains('Mbah POR', $candirejo['lembur_drivers']);
        $this->assertNotContains('Didik', $candirejo['lembur_drivers']);
        $this->assertNotContains('Maji', $candirejo['lembur_drivers']);
    }

    public function test_no_pseudo_driver_or_lembur_route_is_created(): void
    {
        $result = $this->parser->parse($this->novemberFourthText());

        $allDrivers = collect($result['packages'])->flatMap(fn($p) => $p['drivers'])->all();
        $this->assertNotContains('lembur', array_map('strtolower', $allDrivers));
        foreach ($result['packages'] as $pkg) {
            $this->assertStringNotContainsStringIgnoringCase('lembur', $pkg['route_name']);
        }
    }

    public function test_same_route_with_different_casing_merges(): void
    {
        $result = $this->parser->parse(<<<'TXT'
        4 11 26 rabu
        Paket candirejo nganjuk
        1. Gun
        paket Candirejo nganjuk
        1. Toni
        TXT);

        $this->assertCount(1, $result['packages']);
        $this->assertEqualsCanonicalizing(['Gun', 'Toni'], $result['packages'][0]['drivers']);
    }

    public function test_lembur_without_route_marks_the_previous_package(): void
    {
        $result = $this->parser->parse(<<<'TXT'
        4 11 26 rabu
        Paket candirejo nganjuk
        1. Gun
        2. Toni
        lembur
        1. Gun
        TXT);

        $this->assertCount(1, $result['packages']);
        $this->assertSame(['Gun'], $result['packages'][0]['lembur_drivers'] ?? []);
        $this->assertSame([], $result['lembur_unmatched'] ?? []);
    }

    public function test_lembur_with_unknown_route_is_reported_not_created(): void
    {
        $result = $this->parser->parse(<<<'TXT'
        4 11 26 rabu
        Paket candirejo nganjuk
        1. Gun
        lembur paket gendingan ngawi
        1. Toni
        TXT);

        $this->assertCount(1, $result['packages']);
        $this->assertSame([], $result['packages'][0]['lembur_drivers'] ?? []);
        $this->assertCount(1, $result['lembur_unmatched'] ?? []);
    }
}
