<?php

namespace Tests\Unit\Services;

use App\Models\Tujuan;
use App\Services\Ml\NaiveBayesClassifier;
use App\Services\Ml\TujuanMlData;
use App\Services\Ml\TujuanMlService;
use App\Services\RitaseFuzzyMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TujuanMlTest extends TestCase
{
    use RefreshDatabase;
    public function test_classifier_memprediksi_benar_dan_confidence_valid(): void
    {
        $clf = new NaiveBayesClassifier();
        $clf->train(
            ['paket overlay ngawi', 'overlay ngawi', 'patching blitar', 'blitar', 'pare kediri'],
            ['ngawi', 'ngawi', 'blitar', 'blitar', 'kediri']
        );

        $result = $clf->predict('overlay ngawi');
        $this->assertSame('ngawi', $result['label']);
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
    }

    public function test_classifier_tahan_typo(): void
    {
        $clf = new NaiveBayesClassifier();
        $data = TujuanMlData::routeDataset(['Paket overlay kandangan ngawi', 'Paket cmm pulwan blitar']);
        $clf->train($data['samples'], $data['labels']);

        // satu huruf hilang, tetap ke label benar
        $this->assertSame(
            'Paket overlay kandangan ngawi',
            $clf->predict('paket overlay kandangan ngaw')['label']
        );
    }

    public function test_json_roundtrip_tidak_mengubah_prediksi(): void
    {
        $clf = new NaiveBayesClassifier();
        $data = TujuanMlData::routeDataset(['cmm kedungmiri ngawi', 'jembatan kaliombo kediri']);
        $clf->train($data['samples'], $data['labels']);

        $restored = NaiveBayesClassifier::fromArray($clf->toArray());
        $this->assertSame(
            $clf->predict('kedungmiri ngawi')['label'],
            $restored->predict('kedungmiri ngawi')['label']
        );
        $this->assertTrue($restored->isTrained());
    }

    public function test_dataset_kabupaten_memuat_madiun_dan_tulungagung(): void
    {
        $data = TujuanMlData::kabupatenDataset();
        $this->assertContains('Madiun', $data['labels']);
        $this->assertContains('Tulungagung', $data['labels']);
        $this->assertNotEmpty($data['samples']);
    }

    public function test_ml_tidak_mengganggu_tanpa_model_terlatih(): void
    {
        // Tanpa file model, service mengembalikan null (perilaku = rule saja).
        Storage::fake('local');
        TujuanMlService::flushCache();
        $service = new TujuanMlService();

        $this->assertNull($service->predictTujuan('rute entah apa'));
        $this->assertNull($service->predictKabupaten('rute entah apa'));
    }

    public function test_match_routes_exact_tetap_100_tanpa_model(): void
    {
        Storage::fake('local');
        TujuanMlService::flushCache();
        Tujuan::create(['nama' => 'Paket overlay kandangan ngawi', 'status' => 'aktif']);

        $matcher = new RitaseFuzzyMatcher();
        $results = $matcher->matchRoutes(['Paket overlay kandangan ngawi']);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['matched']);
        $this->assertSame(100.0, $results[0]['confidence']);
    }
}
