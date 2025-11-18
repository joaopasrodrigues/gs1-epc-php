<?php
namespace Epc\Schemes;

use Epc\Utils\BitReader;

/**
 * Decoder for GIAI-96 (EPC header 0x34).
 */
class GIAI96
{
    private const HEADER = 0x34;

    private static array $partitionTable = [
        0 => ['cpBits' => 40, 'refBits' => 4,  'cpDigits' => 12, 'refDigits' => 1],
        1 => ['cpBits' => 37, 'refBits' => 7,  'cpDigits' => 11, 'refDigits' => 2],
        2 => ['cpBits' => 34, 'refBits' => 10, 'cpDigits' => 10, 'refDigits' => 3],
        3 => ['cpBits' => 30, 'refBits' => 14, 'cpDigits' => 9,  'refDigits' => 4],
        4 => ['cpBits' => 27, 'refBits' => 17, 'cpDigits' => 8,  'refDigits' => 5],
        5 => ['cpBits' => 24, 'refBits' => 20, 'cpDigits' => 7,  'refDigits' => 6],
        6 => ['cpBits' => 20, 'refBits' => 24, 'cpDigits' => 6,  'refDigits' => 7],
    ];

    public static function decodeRaw(string $bytes): array
    {
        if (strlen($bytes) !== 12) {
            throw new \InvalidArgumentException("GIAI-96 requires exactly 12 bytes (96 bits)");
        }

        $r = new BitReader($bytes);
        $header = $r->readBits(8);
        if ($header !== self::HEADER) {
            throw new \InvalidArgumentException(sprintf("Header mismatch: expected 0x%02X got 0x%02X", self::HEADER, $header));
        }

        $filter = $r->readBits(3);
        $partition = $r->readBits(3);
        $pt = self::$partitionTable[$partition];

        $companyPrefix = $r->readBits($pt['cpBits']);
        $ref = $r->readBits($pt['refBits']);
        $serial = $r->readBits(38);

        $companyPrefixStr = str_pad((string)$companyPrefix, $pt['cpDigits'], '0', STR_PAD_LEFT);
        $refStr = str_pad((string)$ref, $pt['refDigits'], '0', STR_PAD_LEFT);

        $urn = sprintf('urn:epc:id:giai:%s.%s.%s', $companyPrefixStr, $refStr, (string)$serial);

        return [
            'scheme' => 'giai-96',
            'header' => $header,
            'filter' => $filter,
            'partition' => $partition,
            'company_prefix' => $companyPrefixStr,
            'reference' => $refStr,
            'serial' => (string)$serial,
            'urn' => $urn,
        ];
    }
}
