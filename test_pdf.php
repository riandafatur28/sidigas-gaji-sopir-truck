<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/gaji/slip-pdf/1', 'GET');
$response = $kernel->handle($request);

file_put_contents(__DIR__ . '/test-slip.pdf', $response->getContent());
echo 'done: ' . strlen($response->getContent()) . ' bytes';
