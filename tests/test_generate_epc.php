<?php
require_once 'Partitions.php';
require_once 'Translators.php';
require_once 'Encoder.php';

use Gs1Epc\Translators;
use Gs1Epc\Encoder;

$testCases = [
    [
        'name'     => 'SGTIN-96 Retail Item',
        'scheme'   => 'SGTIN-96',
        'gs1_key'  => '00614141123452', // GTIN
        'serial'   => '6789',
        'prefix'   => '0614141',
        'ref'      => '12345',
        'filter'   => 1,
        'expected' => '3034257BF400B6C000001A85'
    ],
    [
        'name'     => 'SSCC-96 Logistics Case',
        'scheme'   => 'SSCC-96',
        'gs1_key'  => '106141411234567890', // SSCC
        'serial'   => '0', // Not used for SSCC-96 (ref is the serial)
        'prefix'   => '6141411',
        'ref'      => '1123456789', // Extension Digit (1) + Reference
        'filter'   => 1,
        'expected' => '3134257BF442F2D195000000'
    ],
    [
        'name'     => 'SGLN-96 Location',
        'scheme'   => 'SGLN-96',
        'gs1_key'  => '0614141123452', // GLN
        'serial'   => '123', // Extension
        'prefix'   => '0614141',
        'ref'      => '12345',
        'filter'   => 0,
        'expected' => '3204257BF400B6C00000007B'
    ],
    [
        'name'     => 'GRAI-96 Returnable Asset',
        'scheme'   => 'GRAI-96',
        'gs1_key'  => '00614141123452', // Asset Type
        'serial'   => '12345',
        'prefix'   => '0614141',
        'ref'      => '12345',
        'filter'   => 0,
        'expected' => '3304257BF400B6C000003039'
    ]
];

echo "--- EPC Encoding Validation Suite ---\n\n";

foreach ($testCases as $case) {
    try {
        // 1. Test Translation to URI
        $uri = "";
        switch($case['scheme']) {
            case 'SGTIN-96': $uri = Translators::sgtinToUri($case['gs1_key'], $case['serial'], strlen($case['prefix'])); break;
            case 'SSCC-96':  $uri = Translators::ssccToUri($case['gs1_key'], strlen($case['prefix'])); break;
            case 'SGLN-96':  $uri = Translators::sglnToUri($case['gs1_key'], $case['serial'], strlen($case['prefix'])); break;
            case 'GRAI-96':  $uri = Translators::graiToUri($case['gs1_key'], $case['serial'], strlen($case['prefix'])); break;
        }

        // 2. Test Encoding to Hex
        $actualHex = Encoder::toHex($case['scheme'], $case['filter'], $case['prefix'], $case['ref'], $case['serial']);

        $status = ($actualHex === $case['expected']) ? "✅ PASS" : "❌ FAIL";
        
        echo "[{$case['name']}]\n";
        echo "  URI:    $uri\n";
        echo "  Result: $actualHex\n";
        if ($status === "❌ FAIL") echo "  Expected: {$case['expected']}\n";
        echo "  Status: $status\n\n";

    } catch (Exception $e) {
        echo "❌ ERROR in {$case['name']}: " . $e->getMessage() . "\n\n";
    }
}
