<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Sopir;
$names = ['Agus','Agus toska','Agus kediri','Avit','Toni','Didik','Soim'];
foreach ($names as $n) {
    $list = Sopir::where('nama','like','%'.$n.'%')->get(['kode_sopir','nama','status']);
    echo "== sopir like '$n': ";
    if ($list->isEmpty()) { echo "(none)\n"; continue; }
    foreach ($list as $s) echo "[".$s->kode_sopir."/".$s->nama."/".$s->status."] ";
    echo "\n";
}
echo "\nSPR-009,016,018 (kedawung):\n";
foreach (Sopir::whereIn('kode_sopir',['SPR-009','SPR-016','SPR-018'])->get(['kode_sopir','nama']) as $s) echo "  ".$s->kode_sopir." = ".$s->nama."\n";
echo "Banjarejo sopirs (SPR-006..070 used):\n";
$banj = ['SPR-006','SPR-007','SPR-012','SPR-014','SPR-019','SPR-020','SPR-021','SPR-022','SPR-023','SPR-024','SPR-025','SPR-026','SPR-029','SPR-050','SPR-052','SPR-063','SPR-064','SPR-065','SPR-066','SPR-067','SPR-068','SPR-069','SPR-070'];
foreach (Sopir::whereIn('kode_sopir',$banj)->get(['kode_sopir','nama'])->sortBy('kode_sopir') as $s) echo "  ".$s->kode_sopir." = ".$s->nama."\n";
echo "\nOverlay sopirs:\n";
$ov = ['SPR-001','SPR-002','SPR-003','SPR-004','SPR-005','SPR-008','SPR-010','SPR-011','SPR-013','SPR-015','SPR-017','SPR-030','SPR-043','SPR-053','SPR-054','SPR-056','SPR-057','SPR-058','SPR-059','SPR-060','SPR-061','SPR-062'];
foreach (Sopir::whereIn('kode_sopir',$ov)->get(['kode_sopir','nama'])->sortBy('kode_sopir') as $s) echo "  ".$s->kode_sopir." = ".$s->nama."\n";