<?php
// === ONCE: Run after first upload to create DB tables ===
// Akses: https://namakamu.infinityfreeapp.com/migrate.php?key=rahasia123
// Hapus file ini setelah selesai!

if (!isset($_GET['key']) || $_GET['key'] !== 'rahasia123') {
    die('Access denied');
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$kernel->call('migrate', ['--force' => true]);
$kernel->call('key:generate', ['--force' => true]);

echo "Migrasi & key generated selesai! HAPUS file ini sekarang.";
