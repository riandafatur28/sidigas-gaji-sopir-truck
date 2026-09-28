<?php

declare(strict_types=1);

namespace App\Services\Ml;

use App\Services\RitaseCreator;

/**
 * Pembangun dataset latih sintetis untuk klasifikasi rute & kabupaten.
 *
 * Sumber: data master (nama tujuan, peta kecamatan) + variasi
 * deterministik (case, prefix, typo). Deterministik = model yang
 * dilatih hari ini identik dengan yang dilatih besok.
 */
class TujuanMlData
{
    /** @return list<string> */
    public static function routePrefixes(): array
    {
        return ['paket', 'overlay', 'cmm', 'patching', 'kormuling', 'rekon', 'bondan', 'gabungan', 'rombongan'];
    }

    /**
     * Varian latih untuk satu nama tujuan kanonis.
     *
     * @param bool $withTypos sertakan varian typo (untuk uji holdout)
     * @return list<string>
     */
    public static function routeVariants(string $canonical, bool $withTypos = true): array
    {
        $variants = [$canonical, strtolower($canonical)];

        $stripped = $canonical;
        foreach (self::routePrefixes() as $prefix) {
            if (str_starts_with(strtolower($stripped), $prefix . ' ')) {
                $stripped = trim(substr($stripped, strlen($prefix) + 1));
            }
        }
        if ($stripped !== $canonical) {
            $variants[] = $stripped;
            $variants[] = strtolower($stripped);
        }

        if ($withTypos) {
            $base = strtolower($stripped !== $canonical ? $stripped : $canonical);
            $variants = array_merge($variants, self::typoVariants($base));
        }

        return array_values(array_unique($variants));
    }

    /**
     * Varian typo deterministik: hapus 1 huruf tengah + tukar 2 huruf.
     *
     * @return list<string>
     */
    public static function typoVariants(string $text): array
    {
        $out = [];
        $len = mb_strlen($text);
        if ($len >= 6) {
            $mid = (int) ($len / 2);
            $out[] = mb_substr($text, 0, $mid) . mb_substr($text, $mid + 1);
        }
        if ($len >= 5) {
            $pos = 1;
            $chars = mb_str_split($text);
            if (isset($chars[$pos], $chars[$pos + 1]) && $chars[$pos] !== ' ' && $chars[$pos + 1] !== ' ') {
                [$chars[$pos], $chars[$pos + 1]] = [$chars[$pos + 1], $chars[$pos]];
                $out[] = implode('', $chars);
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Dataset rute: [samples, labels]. Label = nama tujuan kanonis.
     *
     * @param list<string> $canonicalNames
     * @return array{samples: list<string>, labels: list<string>}
     */
    public static function routeDataset(array $canonicalNames, bool $withTypos = true): array
    {
        $samples = $labels = [];
        foreach ($canonicalNames as $name) {
            foreach (self::routeVariants($name, $withTypos) as $variant) {
                $samples[] = $variant;
                $labels[] = $name;
            }
        }
        return ['samples' => $samples, 'labels' => $labels];
    }

    /**
     * Dataset kabupaten: [samples, labels].
     * Label dihitung dengan aturan yang sama seperti runtime
     * (eksplisit dulu, lalu kecamatan) agar ML menggeneralisasi
     * pola yang sama, termasuk ke varian typo.
     *
     * @return array{samples: list<string>, labels: list<string>}
     */
    public static function kabupatenDataset(): array
    {
        $creator = new RitaseCreator();
        $samples = $labels = [];

        $add = function (string $sample) use (&$samples, &$labels, $creator): void {
            $label = $creator->guessKabupaten($sample);
            if ($label === 'Lainnya') {
                return;
            }
            $samples[] = $sample;
            $labels[] = $label;
            foreach (self::typoVariants(strtolower($sample)) as $typo) {
                $samples[] = $typo;
                $labels[] = $label;
            }
        };

        foreach (RitaseCreator::kabupatenKeywords() as $kw => $kab) {
            $add($kw);
            $add($kab);
        }
        foreach (RitaseCreator::kecamatanMap() as $kec => $kab) {
            $add($kec);
        }

        return ['samples' => $samples, 'labels' => $labels];
    }

    /**
     * Evaluasi holdout: latih dengan varian dasar, uji dengan varian typo.
     *
     * @return array{total: int, correct: int, accuracy: float}
     */
    public static function holdoutAccuracy(NaiveBayesClassifier $prototype, array $canonicalNames, callable $labelOf): array
    {
        $trainSamples = $trainLabels = $testSamples = $testLabels = [];
        foreach ($canonicalNames as $name) {
            foreach (self::routeVariants($name, false) as $variant) {
                $trainSamples[] = $variant;
                $trainLabels[] = $labelOf($name);
            }
            $label = $labelOf($name);
            foreach (self::typoVariants(strtolower($name)) as $typo) {
                $testSamples[] = $typo;
                $testLabels[] = $label;
            }
        }

        $clf = clone $prototype;
        $clf->train($trainSamples, $trainLabels);

        $correct = 0;
        foreach ($testSamples as $i => $sample) {
            if ($clf->predict($sample)['label'] === $testLabels[$i]) {
                $correct++;
            }
        }
        $total = count($testSamples);

        return ['total' => $total, 'correct' => $correct, 'accuracy' => $total > 0 ? $correct / $total : 0.0];
    }
}
