<?php

declare(strict_types=1);

namespace App\Services\Ml;

/**
 * Multinomial Naive Bayes untuk teks pendek (nama rute/tujuan).
 *
 * Dipakai sebagai fallback saat rule/fuzzy tidak match: selalu
 * mengembalikan confidence (softmax posterior) sehingga pemanggil bisa
 * menolak prediksi lemah. Tanpa dependensi eksternal; model disimpan
 * sebagai JSON via toArray()/fromArray().
 *
 * Fitur: unigram kata + trigram karakter (tahan typo/singkatan).
 */
class NaiveBayesClassifier
{
    /** @var list<string> label per indeks */
    private array $labels = [];

    /** @var array<int, float> log prior per indeks label */
    private array $logPrior = [];

    /** @var array<int, array<string, float>> log likelihood token per label */
    private array $logLikelihood = [];

    /** @var array<int, float> log prob token tak dikenal per label */
    private array $logUnknown = [];

    private float $alpha = 1.0;

    /**
     * @param list<string> $samples
     * @param list<string> $labels  sejajar dengan $samples
     */
    public function train(array $samples, array $labels, float $alpha = 1.0): void
    {
        $this->alpha = $alpha;
        $this->labels = array_values(array_unique($labels));
        $labelIdx = array_flip($this->labels);

        $tokenCounts = []; // [labelIdx][token] => count
        $totalTokens = []; // [labelIdx] => int
        $docCounts = array_fill(0, count($this->labels), 0);
        $vocab = [];

        foreach ($samples as $i => $sample) {
            $li = $labelIdx[$labels[$i]];
            $docCounts[$li]++;
            foreach (self::tokenize($sample) as $tok) {
                $tokenCounts[$li][$tok] = ($tokenCounts[$li][$tok] ?? 0) + 1;
                $totalTokens[$li] = ($totalTokens[$li] ?? 0) + 1;
                $vocab[$tok] = true;
            }
        }

        $vocabSize = count($vocab);
        $totalDocs = count($samples);

        foreach ($this->labels as $li => $label) {
            $this->logPrior[$li] = log($docCounts[$li] / $totalDocs);
            $denom = ($totalTokens[$li] ?? 0) + $alpha * $vocabSize;
            $this->logUnknown[$li] = log($alpha / $denom);
            foreach ($tokenCounts[$li] ?? [] as $tok => $count) {
                $this->logLikelihood[$li][$tok] = log(($count + $alpha) / $denom);
            }
        }
    }

    /** @return array<string, float> label => log posterior */
    public function predictScores(string $text): array
    {
        $tokens = self::tokenize($text);
        $freq = array_count_values($tokens);
        $scores = [];
        foreach ($this->labels as $li => $label) {
            $score = $this->logPrior[$li];
            foreach ($freq as $tok => $count) {
                $score += $count * ($this->logLikelihood[$li][$tok] ?? $this->logUnknown[$li]);
            }
            $scores[$label] = $score;
        }
        return $scores;
    }

    /** @return array{label: string, confidence: float} */
    public function predict(string $text): array
    {
        $scores = $this->predictScores($text);
        arsort($scores);
        $best = (string) array_key_first($scores);

        $max = $scores[$best];
        $sumExp = 0.0;
        foreach ($scores as $s) {
            $sumExp += exp($s - $max);
        }

        return ['label' => $best, 'confidence' => 1.0 / $sumExp];
    }

    public function isTrained(): bool
    {
        return $this->labels !== [];
    }

    /** @return list<string> */
    public static function tokenize(string $text): array
    {
        $t = mb_strtolower(trim($text));
        $words = preg_split('/\s+/u', $t, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = $words;
        foreach ($words as $w) {
            $padded = '^' . $w . '$';
            $len = mb_strlen($padded);
            for ($i = 0; $i + 3 <= $len; $i++) {
                $tokens[] = mb_substr($padded, $i, 3);
            }
        }
        return $tokens;
    }

    public function toArray(): array
    {
        return [
            'labels' => $this->labels,
            'logPrior' => $this->logPrior,
            'logLikelihood' => $this->logLikelihood,
            'logUnknown' => $this->logUnknown,
            'alpha' => $this->alpha,
        ];
    }

    public static function fromArray(array $data): self
    {
        $clf = new self();
        $clf->labels = $data['labels'] ?? [];
        $clf->logPrior = $data['logPrior'] ?? [];
        $clf->logLikelihood = $data['logLikelihood'] ?? [];
        $clf->logUnknown = $data['logUnknown'] ?? [];
        $clf->alpha = (float) ($data['alpha'] ?? 1.0);
        return $clf;
    }
}
