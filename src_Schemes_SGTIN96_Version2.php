<?php
namespace Epc\Schemes;

use Epc\Utils\BitReader;

/**
 * Decoder for SGTIN-96 (EPC header 0x30).
 *
 * Reference (simplified):
 * - Header: 8 bits (0x30)
 * - Filter: 3 bits
 * - Partition: 3 bits
 * - Company prefix + item reference: 44 bits (partition determines split)
 * - Serial: 38 bits
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

    /**
     * Decode a 12-byte (96-bit) EPC into an associative array.
     *
     * @param string $bytes 12-byte string (binary)
     * @return array
     * @throws \InvalidArgumentException
     */
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

        $cpBits = $pt['cpBits'];
        $itemBits = $pt['itemBits'];

        $companyPrefix = $r->readBits($cpBits);
        $itemRef = $r->readBits($itemBits);
        $serial = $r->readBits(38);

        // Turn numeric fields into zero-padded strings with appropriate digit counts
        $companyPrefixDigits = $pt['cpDigits'];
        $itemRefDigits = $pt['itemDigits'];

        $companyPrefixStr = str_pad((string)$companyPrefix, $companyPrefixDigits, "0", STR_PAD_LEFT);
        $itemRefStr = str_pad((string)$itemRef, $itemRefDigits, "0", STR_PAD_LEFT);

        // The SGTIN item reference from the partition table is the 13-digit GS1 item reference (without check digit)
        $gtin13 = $companyPrefixStr . $itemRefStr; // 13 digits
        if (strlen($gtin13) < 13) {
            $gtin13 = str_pad($gtin13, 13, '0', STR_PAD_LEFT);
        }

        // Construct URN: urn:epc:id:sgtin:<company>.<item>.<serial>
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
            'gtin14' => self::gtin14FromGtin13($gtin13),
        ];
    }

    /**
     * Convert a 13-digit GS1 element (companyPrefix+itemRef) to GTIN-14
     * by prefixing an indicator digit (0) and computing the check digit.
     *
     * NOTE: this is a practical default (indicator 0). Different applications may use a different indicator.
     *
     * @param string $gtin13 13 digits (companyprefix+itemref)
     * @return string GTIN-14 (14 digits with calculated check digit)
     */
    public static function gtin14FromGtin13(string $gtin13): string
    {
        $gtin13 = preg_replace('/[^0-9]/', '', $gtin13);
        if (strlen($gtin13) !== 13) {
            $gtin13 = str_pad($gtin13, 13, '0', STR_PAD_LEFT);
        }
        // Prefix indicator 0 (common default)
        $withoutCheck = '0' . $gtin13; // 14 digits, last digit to be computed below
        // Compute check digit for GTIN-14 using the first 13 digits (positions 1..13)
        $left13 = substr($withoutCheck, 0, 13);
        $digits = array_map('intval', str_split($left13));
        $reversed = array_reverse($digits);
        $sum = 0;
        foreach ($reversed as $i => $d) {
            $mult = ($i % 2 === 0) ? 3 : 1;
            $sum += $d * $mult;
        }
        $mod = $sum % 10;
        $check = ($mod === 0) ? 0 : (10 - $mod);
        return $withoutCheck . (string)$check;
    }
}