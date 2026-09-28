<?php

declare(strict_types=1);

namespace App\Services\Ml;

use App\Models\Tujuan;
use Illuminate\Support\Facades\Storage;

/**
 * Klasifikasi rute & kabupaten dengan Naive Bayes (lokal, tanpa internet).
 *
 * Dipakai HANYA sebagai fallback: rule/fuzzy yang sudah match tidak
 * pernah di-override. Jika file model belum dilatih (ml:train-tujuan),
 * semua prediksi mengembalikan null dan perilaku = rule saja.
 */
class TujuanMlService
{
    /** Confidence minimum agar prediksi ML diterima. */
    public const THRESHOLD = 0.8;

    /** @var array<string, ?NaiveBayesClassifier> */
    private static array $cache = [];

    /**
     * @return array{kode_tujuan: string, nama: string, confidence: float}|null
     */
    public function predictTujuan(string $routeName): ?array
    {
        $clf = $this->load('tujuan_nb.json');
        if ($clf === null || trim($routeName) === '') {
            return null;
        }

        $result = $clf->predict($routeName);
        if ($result['confidence'] < self::THRESHOLD) {
            return null;
        }

        $tujuan = Tujuan::where('nama', $result['label'])->first();
        if ($tujuan === null) {
            return null;
        }

        return [
            'kode_tujuan' => $tujuan->kode_tujuan,
            'nama' => $tujuan->nama,
            'confidence' => round($result['confidence'], 4),
        ];
    }

    /**
     * @return array{kabupaten: string, confidence: float}|null
     */
    public function predictKabupaten(string $routeName): ?array
    {
        $clf = $this->load('kabupaten_nb.json');
        if ($clf === null || trim($routeName) === '') {
            return null;
        }

        $result = $clf->predict($routeName);
        if ($result['confidence'] < self::THRESHOLD || $result['label'] === 'Lainnya') {
            return null;
        }

        return ['kabupaten' => $result['label'], 'confidence' => round($result['confidence'], 4)];
    }

    private function load(string $file): ?NaiveBayesClassifier
    {
        if (array_key_exists($file, self::$cache)) {
            return self::$cache[$file];
        }

        $path = 'ml/' . $file;
        if (!Storage::disk('local')->exists($path)) {
            self::$cache[$file] = null;
            return null;
        }

        try {
            $data = json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
            $clf = NaiveBayesClassifier::fromArray($data);
            self::$cache[$file] = $clf->isTrained() ? $clf : null;
        } catch (\Throwable) {
            self::$cache[$file] = null;
        }

        return self::$cache[$file];
    }

    public function save(NaiveBayesClassifier $clf, string $file): void
    {
        Storage::disk('local')->put(
            'ml/' . $file,
            json_encode($clf->toArray(), JSON_THROW_ON_ERROR)
        );
        self::$cache[$file] = $clf;
    }

    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
