<?php
namespace Epc\Schemes;

use Epc\Utils\BitReader;

/**
 * Decoder for SGLN-96 (EPC header 0x32).
 */
class SGLN96
{
    private const HEADER = 0x32;

    private static array $partitionTable = [
        0 => ['cpBits' => 40, 'locBits' => 4,  'cpDigits' => 12, 'locDigits' => 1],
        1 => ['cpBits' => 37, 'locBits' => 7,  'cpDigits' => 11, 'locDigits' => 2],
        2 => ['cpBits' => 34, 'locBits' => 10, 'cpDigits' => 10, 'locDigits' => 3],
        3 => ['cpBits' => 30, 'locBits' => 14, 'cpDigits' => 9,  'locDigits' => 4],
        4 => ['cpBits' => 27, 'locBits' => 17, 'cpDigits' => 8,  'locDigits' => 5],
        5 => ['cpBits' => 24, 'locBits' => 20, 'cpDigits' => 7,  'locDigits' => 6],
        6 => ['cpBits' => 20, 'locBits' => 24, 'cpDigits' => 6,  'locDigits' => 7],
    ];

    public static function decodeRaw(string $bytes): array
    {
        if (strlen($bytes) !== 12) {
            throw new \InvalidArgumentException("SGLN-96 requires exactly 12 bytes (96 bits)");
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
        $locationRef = $r->readBits($pt['locBits']);
        $extension = $r->readBits(38);

        $companyPrefixStr = str_pad((string)$companyPrefix, $pt['cpDigits'], '0', STR_PAD_LEFT);
        $locationRefStr = str_pad((string)$locationRef, $pt['locDigits'], '0', STR_PAD_LEFT);

        $urn = sprintf('urn:epc:id:sgln:%s.%s.%s', $companyPrefixStr, $locationRefStr, (string)$extension);

        return [
            'scheme' => 'sgln-96',
            'header' => $header,
            'filter' => $filter,
            'partition' => $partition,
            'company_prefix' => $companyPrefixStr,
            'location_reference' => $locationRefStr,
            'extension' => (string)$extension,
            'urn' => $urn,
        ];
    }
}
