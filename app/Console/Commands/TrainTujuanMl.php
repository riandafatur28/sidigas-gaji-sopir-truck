<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tujuan;
use App\Services\Ml\NaiveBayesClassifier;
use App\Services\Ml\TujuanMlData;
use App\Services\Ml\TujuanMlService;
use Illuminate\Console\Command;

class TrainTujuanMl extends Command
{
    protected $signature = 'ml:train-tujuan';

    protected $description = 'Latih model Naive Bayes untuk klasifikasi rute & kabupaten dari data master';

    public function handle(TujuanMlService $service): int
    {
        $rows = [];
        $skipRoute = false;

        try {
            $names = Tujuan::orderBy('id')->pluck('nama')->all();
        } catch (\Throwable) {
            $names = [];
            $skipRoute = true;
            $this->warn('Database tidak tersedia. Model rute dilewati.');
        }

        if (!$skipRoute && $names !== []) {
            $routeData = TujuanMlData::routeDataset($names);
            $routeClf = new NaiveBayesClassifier();
            $routeClf->train($routeData['samples'], $routeData['labels']);
            $service->save($routeClf, 'tujuan_nb.json');

            $routeEval = TujuanMlData::holdoutAccuracy(
                new NaiveBayesClassifier(),
                $names,
                fn (string $name) => $name
            );

            $rows[] = [
                'rute -> tujuan',
                count($routeData['samples']),
                $routeEval['total'],
                $routeEval['correct'],
                number_format($routeEval['accuracy'] * 100, 1) . '%',
            ];
        }

        $kabData = TujuanMlData::kabupatenDataset();
        $kabClf = new NaiveBayesClassifier();
        $kabClf->train($kabData['samples'], $kabData['labels']);
        $service->save($kabClf, 'kabupaten_nb.json');

        $kabEval = TujuanMlData::holdoutAccuracy(
            new NaiveBayesClassifier(),
            array_keys(array_count_values($kabData['labels'])),
            fn (string $label) => $label
        );

        $rows[] = [
            'teks -> kabupaten',
            count($kabData['samples']),
            $kabEval['total'],
            $kabEval['correct'],
            number_format($kabEval['accuracy'] * 100, 1) . '%',
        ];

        $this->info('Model tersimpan di storage/app/ml/.');
        $this->table(
            ['Model', 'Sampel latih', 'Uji holdout (typo)', 'Benar', 'Akurasi'],
            $rows
        );

        return self::SUCCESS;
    }
}
