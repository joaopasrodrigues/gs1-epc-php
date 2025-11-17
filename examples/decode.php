<?php
require __DIR__ . '/../vendor/autoload.php';

use Epc\Decoder;

$examples = [
    'SGTIN-96 (example)' => '3034257BF7194E4000001A85',
];

$dec = new Decoder();

foreach ($examples as $k => $hex) {
    echo "Example: {$k}\n";
    try {
        $res = $dec->decodeHex($hex);
        print_r($res);
    } catch (Throwable $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
    echo str_repeat('-', 40) . PHP_EOL;
}