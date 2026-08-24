<?php
// Reproduce the parser with the exact user input
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\RitaseParserService;

$text = <<<TXT
12 08 26 rabu
   Agung patching kedawung jombang
   Avit patching kedawung jombang
   Toni patching kedawung jombang
   Paket overlay sido laju ngawi
 1. Agus toska
 2. Very karangsono
 3. Wawan kancel
 4. Ginem
 5. Kola
 6. Firsa
 7. Gombloh ngawi
 8. Yanto
 9. Gun
 10. Kuwat
 11. Giono
 12. Angga bayu putra
 13. Subro
 14. Riki
 15. Wahyu
 16. Anjar
 17. Yuri
 18. Aripin
 19. Nur fahmi
 20. Santoso
 21. Bondan
 22. Andri Wilangan
     Paket Banjarejo ngawi
 1. Didik
 2. Soim
 3. Wilujeng
 4. Prapto
 5. Mbah por
 6. Budi tarokan
 7. Nanang kediri
 8. Narji
 9. Bajal
 10. Gilang kediri
 11. Berok
 12. Topa kediri
 13. Pak nyoto kediri
 14. Agus kediri
 15. Topik
 16. Eka bence
 17. Adip tiripan
 18. Sumanto
 19. Radib
 20. Wahid
 21. Mamuk
 22. Wakub
 23. Eko Wilangan
 24. Witoyo Wilangan
 25. Avit
 26. Toni
TXT;

$parser = new RitaseParserService();
$parsed = $parser->parse($text);

echo "===== DATE: " . ($parsed['date'] ?? 'NULL') . " =====\n";
echo "header_kode_tujuan: " . ($parsed['header_kode_tujuan'] ?? 'NULL') . "\n\n";

foreach ($parsed['packages'] as $i => $pkg) {
    echo "--- Package #" . ($i+1) . " ---\n";
    echo "route: [" . $pkg['route_name'] . "]\n";
    echo "is_bongkar: " . (!empty($pkg['is_bongkar']) ? 'Y' : 'N') . "\n";
    echo "is_rit_ke_2: " . (!empty($pkg['is_rit_ke_2']) ? 'Y' : 'N') . "\n";
    echo "drivers (" . count($pkg['drivers']) . "): " . implode(', ', $pkg['drivers']) . "\n\n";
}

// Check the interesting drivers:
$allDrivers = collect($parsed['packages'])->pluck('drivers')->flatten()->unique()->values()->all();
echo "===== DRIVER MATCHES =====\n";
$driverMatches = $parser->matchDrivers($allDrivers);
foreach ($driverMatches as $dm) {
    if (in_array(strtolower($dm['input_name']), ['avit', 'toni', 'agus toska', 'agus kediri', 'agung'])) {
        echo sprintf(
            "%-15s -> matched=%s confidence=%.2f sopir=%s\n",
            $dm['input_name'],
            $dm['matched'] ? 'Y' : 'N',
            $dm['confidence'],
            $dm['sopir'] ? $dm['sopir']->nama . ' (' . $dm['sopir']->kode_sopir . ')' : 'NULL'
        );
    }
}

echo "\n===== ROUTE MATCHES =====\n";
$routeNames = collect($parsed['packages'])
    ->reject(fn($p) => !empty($p['is_bongkar']))
    ->pluck('route_name')->unique()->values()->all();
$routeMatches = $parser->matchRoutes($routeNames);
foreach ($routeMatches as $rm) {
    echo sprintf(
        "%-35s -> matched=%s confidence=%.2f tujuan=%s (%s)\n",
        $rm['input_route'],
        $rm['matched'] ? 'Y' : 'N',
        $rm['confidence'],
        $rm['tujuan'] ? $rm['tujuan']->nama : 'NULL',
        $rm['tujuan'] ? $rm['tujuan']->kode_tujuan : 'NULL'
    );
}