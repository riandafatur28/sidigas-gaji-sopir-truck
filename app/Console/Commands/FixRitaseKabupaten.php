<?php

namespace App\Console\Commands;

use App\Models\Ritase;
use App\Services\RitaseCreator;
use Illuminate\Console\Command;

class FixRitaseKabupaten extends Command
{
    protected $signature = 'ritase:fix-kabupaten';
    protected $description = 'Re-evaluate kabupaten for ritase records with Lainnya or NULL kabupaten';

    public function handle(): int
    {
        $creator = new RitaseCreator();

        $query = Ritase::query()
            ->where(function ($q) {
                $q->where('kabupaten', 'Lainnya')
                    ->orWhereNull('kabupaten');
            })
            ->with('tujuan');

        $total = $query->count();
        $this->info("Found {$total} ritase records with missing/incorrect kabupaten.");

        if ($total === 0) {
            $this->info('Nothing to fix.');
            return Command::SUCCESS;
        }

        $fixed = 0;
        $failed = 0;

        $query->chunk(100, function ($ritases) use ($creator, &$fixed, &$failed) {
            foreach ($ritases as $ritase) {
                $tujuanNama = $ritase->tujuan?->nama ?? '';
                if (empty($tujuanNama)) {
                    $this->warn("  Skip ritase #{$ritase->id}: no tujuan name");
                    $failed++;
                    continue;
                }

                $newKab = $creator->guessKabupaten($tujuanNama);

                if ($newKab && $newKab !== 'Lainnya') {
                    $ritase->update(['kabupaten' => $newKab]);
                    $this->line("  #{$ritase->id}: \"{$tujuanNama}\" → {$newKab}");
                    $fixed++;
                } else {
                    $this->warn("  #{$ritase->id}: \"{$tujuanNama}\" → still '{$newKab}'");
                    $failed++;
                }
            }
        });

        $this->info("Done. Fixed: {$fixed}, Unchanged: {$failed}");
        return Command::SUCCESS;
    }
}
