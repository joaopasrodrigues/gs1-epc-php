<?php

namespace Epc;

/**
 * EPC FACTORY: The primary entry point for the library.
 */
class Epc {
    /**
     * Converts a GS1 barcode and serial into an RFID Hex string.
     * * @param string $ai      The Application Identifier (e.g., '01' for GTIN, '00' for SSCC)
     * @param string $code    The barcode digits (including check digit)
     * @param string $serial  The serial number (as a string or numeric)
     * @param int    $cpLen   The length of your GS1 Company Prefix (e.g., 7 or 9)
     * @param int    $filter  The Filter Value (Default 1 = POS / Retail)
     */
    public static function fromBarcode(string $ai, string $code, string $serial, int $cpLen, int $filter = 1): string {
        switch ($ai) {
            case '01': // SGTIN (GTIN + Serial)
                $uri = Translators::sgtinToUri($code, $serial, $cpLen);
                break;
            case '00': // SSCC
                $uri = Translators::ssccToUri($code, $cpLen);
                break;
            case '414': // SGLN (GLN + Extension)
                $uri = Translators::sglnToUri($code, $serial, $cpLen);
                break;
            case '8003': // GRAI (Asset Type + Serial)
                $uri = Translators::graiToUri($code, $serial, $cpLen);
                break;
            default:
                throw new \Exception("Unsupported Application Identifier: $ai");
        }

        $parts = self::parseUri($uri);
        $scheme = self::getSchemeName($ai);
        
        return Encoder::toHex($scheme, $filter, $parts['prefix'], $parts['reference'], $parts['serial']);
    }

    private static function getSchemeName($ai) {
        $map = ['01' => 'SGTIN-96', '00' => 'SSCC-96', '414' => 'SGLN-96', '8003' => 'GRAI-96'];
        return $map[$ai];
    }

    private static function parseUri($uri) {
        $parts = explode(':', $uri);
        $data = explode('.', end($parts));
        return [
            'prefix'    => $data[0],
            'reference' => $data[1],
            'serial'    => $data[2] ?? 0
        ];
    }
}

/**
 * TRANSLATORS: Converts human-readable GS1 codes to EPC URIs.
 */
class Translators {
    public static function sgtinToUri($gtin, $serial, $pLen) {
        $gtin = str_pad($gtin, 14, "0", STR_PAD_LEFT);
        return "urn:epc:id:sgtin:" . substr($gtin, 1, $pLen) . "." . substr($gtin, 1 + $pLen, 12 - $pLen) . "." . $serial;
    }

    public static function ssccToUri($sscc, $pLen) {
        $sscc = str_pad($sscc, 18, "0", STR_PAD_LEFT);
        // SSCC Ref = Extension Digit + Serial Reference
        $ref = substr($sscc, 0, 1) . substr($sscc, 1 + $pLen, 16 - $pLen);
        return "urn:epc:id:sscc:" . substr($sscc, 1, $pLen) . "." . $ref . ".0";
    }

    public static function sglnToUri($gln, $ext, $pLen) {
        $gln = str_pad($gln, 13, "0", STR_PAD_LEFT);
        return "urn:epc:id:sgln:" . substr($gln, 0, $pLen) . "." . substr($gln, $pLen, 12 - $pLen) . "." . $ext;
    }

    public static function graiToUri($grai, $serial, $pLen) {
        $grai = ltrim($grai, '0');
        return "urn:epc:id:grai:" . substr($grai, 0, $pLen) . "." . substr($grai, $pLen, 12 - $pLen) . "." . $serial;
    }
}

/**
 * ENCODER: Bit-packs the data into 96-bit Hex.
 */
class Encoder {
    private static $headers = ['SGTIN-96' => 48, 'SSCC-96' => 49, 'SGLN-96' => 50, 'GRAI-96' => 51];

    public static function toHex($scheme, $filter, $prefix, $reference, $serial = 0) {
        $hVal = self::$headers[$scheme];
        $p = Partitions::getByPrefix($prefix);
        
        // 1. Header (8) + Filter (3) + Partition (3)
        $bin = str_pad(decbin($hVal), 8, "0", STR_PAD_LEFT);
        $bin .= str_pad(decbin($filter), 3, "0", STR_PAD_LEFT);
        $bin .= str_pad(decbin($p['index']), 3, "0", STR_PAD_LEFT);

        // 2. Company Prefix + Reference
        $bin .= str_pad(decbin((int)$prefix), $p['prefix_bits'], "0", STR_PAD_LEFT);
        $bin .= str_pad(decbin((int)$reference), $p['ref_bits'], "0", STR_PAD_LEFT);
        
        // 3. Serial / Padding
        $sBits = ($scheme === 'SSCC-96') ? 24 : 38;
        $bin .= str_pad(decbin((int)$serial), $sBits, "0", STR_PAD_LEFT);
        if ($scheme === 'SSCC-96') $bin = str_pad($bin, 96, "0", STR_PAD_RIGHT);

        // 4. Convert Binary to Hex chunks
        $hex = "";
        foreach (str_split($bin, 4) as $chunk) {
            $hex .= dechex(bindec($chunk));
        }
        return strtoupper($hex);
    }
}

/**
 * PARTITIONS: Standard GS1 bit-split definitions.
 */
class Partitions {
    const TABLE = [
        0 => ['prefix_len' => 12, 'prefix_bits' => 40, 'ref_bits' => 4],
        1 => ['prefix_len' => 11, 'prefix_bits' => 37, 'ref_bits' => 7],
        2 => ['prefix_len' => 10, 'prefix_bits' => 34, 'ref_bits' => 10],
        3 => ['prefix_len' => 9,  'prefix_bits' => 30, 'ref_bits' => 14],
        4 => ['prefix_len' => 8,  'prefix_bits' => 27, 'ref_bits' => 17],
        5 => ['prefix_len' => 7,  'prefix_bits' => 24, 'ref_bits' => 20],
        6 => ['prefix_len' => 6,  'prefix_bits' => 20, 'ref_bits' => 24],
    ];

    public static function getByPrefix($prefix) {
        $len = strlen((string)$prefix);
        foreach (self::TABLE as $index => $row) {
            if ($row['prefix_len'] === $len) return array_merge(['index' => $index], $row);
        }
        throw new \Exception("Invalid Company Prefix length: $len");
    }
}