<?php
namespace Epc\Schemes;

use Epc\Utils\BitReader;

/**
 * Decoder for SGTIN-96 (EPC header 0x30).
 */
class SGTIN96
{
    private const HEADER = 0x30;

    private static array $partitionTable = [
        0 => ['cpBits' => 40, 'itemBits' => 4,  'cpDigits' => 12, 'itemDigits' => 1],
        1 => ['cpBits' => 37, 'itemBits' => 7,  'cpDigits' => 11, 'itemDigits' => 2],
        2 => ['cpBits' => 34, 'itemBits' => 10, 'cpDigits' => 10, 'itemDigits' => 3],
        3 => ['cpBits' => 30, 'itemBits' => 14, 'cpDigits' => 9,  'itemDigits' => 4],
        4 => ['cpBits' => 27, 'itemBits' => 17, 'cpDigits' => 8,  'itemDigits' => 5],
        5 => ['cpBits' => 24, 'itemBits' => 20, 'cpDigits' => 7,  'itemDigits' => 6],
        6 => ['cpBits' => 20, 'itemBits' => 24, 'cpDigits' => 6,  'itemDigits' => 7],
    ];

    public static function decodeRaw(string $bytes): array
    {
        if (strlen($bytes) !== 12) {
            throw new \InvalidArgumentException("SGTIN-96 requires exactly 12 bytes (96 bits)");
        }

        $r = new BitReader($bytes);

        $header = $r->readBits(8);
        if ($header !== self::HEADER) {
            throw new \InvalidArgumentException("Header mismatch: expected 0x" . dechex(self::HEADER) . " got 0x" . dechex($header));
        }

        $filter = $r->readBits(3);
        $partition = $r->readBits(3);
        if (!isset(self::$partitionTable[$partition])) {
            throw new \InvalidArgumentException("Invalid partition: {$partition}");
        }
        $pt = self::$partitionTable[$partition];

        $companyPrefix = $r->readBits($pt['cpBits']);
        $itemRef = $r->readBits($pt['itemBits']);
        $serial = $r->readBits(38);

        $companyPrefixStr = str_pad((string)$companyPrefix, $pt['cpDigits'], "0", STR_PAD_LEFT);
        $itemRefStr = str_pad((string)$itemRef, $pt['itemDigits'], "0", STR_PAD_LEFT);

        // The item reference includes the GTIN indicator digit as its first digit.
        // Build the 13-digit GTIN stem as: indicator + company_prefix + item_ref_without_indicator
        $indicatorDigit = substr($itemRefStr, 0, 1);
        $itemRefWithoutIndicator = substr($itemRefStr, 1);

        $gtin13stem = $indicatorDigit . $companyPrefixStr . $itemRefWithoutIndicator;
        if (strlen($gtin13stem) < 13) {
            $gtin13stem = str_pad($gtin13stem, 13, '0', STR_PAD_LEFT);
        }

        $urn = sprintf('urn:epc:id:sgtin:%s.%s.%s', $companyPrefixStr, $itemRefStr, (string)$serial);

        return [
            'scheme' => 'sgtin-96',
            'header' => $header,
            'filter' => $filter,
            'partition' => $partition,
            'company_prefix' => $companyPrefixStr,
            'item_reference' => $itemRefStr,
            'serial' => (string)$serial,
            'urn' => $urn,
            'gtin14' => self::gtin14FromGtin13($gtin13stem),
        ];
    }

    public static function gtin14FromGtin13(string $gtin13): string
    {
        $gtin13 = preg_replace('/[^0-9]/', '', $gtin13);
        if (strlen($gtin13) !== 13) {
            $gtin13 = str_pad($gtin13, 13, '0', STR_PAD_LEFT);
        }
        $digits = array_map('intval', str_split($gtin13));
        $reversed = array_reverse($digits);
        $sum = 0;
        foreach ($reversed as $i => $d) {
            $mult = ($i % 2 === 0) ? 3 : 1;
            $sum += $d * $mult;
        }
        $mod = $sum % 10;
        $check = ($mod === 0) ? 0 : (10 - $mod);
        return $gtin13 . (string)$check;
    }
}
