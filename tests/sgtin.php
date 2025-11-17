<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Epc\Decoder;

$decoder = new Decoder();

// Example EPC (SGTIN-96)
$hex = '3034257BF7194E4000001A85';

try {
    $data = $decoder->decodeHex($hex);
    echo "Decoded scheme: " . $data['scheme'] . PHP_EOL;
    echo "Company prefix: " . $data['company_prefix'] . PHP_EOL;
    echo "Item reference: " . $data['item_reference'] . PHP_EOL;
    echo "Serial: " . $data['serial'] . PHP_EOL;
    echo "URN: " . $data['urn'] . PHP_EOL;
    echo "GTIN-14: " . $data['gtin14'] . PHP_EOL;
} catch (Exception $e) {
    echo "Decode failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}