<?php
namespace Epc\Schemes;

use Epc\Utils\BitReader;

/**
 * Decoder for GRAI-96 (EPC header 0x33).
 */
class GRAI96
{
    private const HEADER = 0x33;

    private static array $partitionTable = [
        0 => ['cpBits' => 40, 'typeBits' => 4,  'cpDigits' => 12, 'typeDigits' => 1],
        1 => ['cpBits' => 37, 'typeBits' => 7,  'cpDigits' => 11, 'typeDigits' => 2],
        2 => ['cpBits' => 34, 'typeBits' => 10, 'cpDigits' => 10, 'typeDigits' => 3],
        3 => ['cpBits' => 30, 'typeBits' => 14, 'cpDigits' => 9,  'typeDigits' => 4],
        4 => ['cpBits' => 27, 'typeBits' => 17, 'cpDigits' => 8,  'typeDigits' => 5],
        5 => ['cpBits' => 24, 'typeBits' => 20, 'cpDigits' => 7,  'typeDigits' => 6],
        6 => ['cpBits' => 20, 'typeBits' => 24, 'cpDigits' => 6,  'typeDigits' => 7],
    ];

    public static function decodeRaw(string $bytes): array
    {
        if (strlen($bytes) !== 12) {
            throw new \InvalidArgumentException("GRAI-96 requires exactly 12 bytes (96 bits)");
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
        $assetType = $r->readBits($pt['typeBits']);
        $serial = $r->readBits(38);

        $companyPrefixStr = str_pad((string)$companyPrefix, $pt['cpDigits'], '0', STR_PAD_LEFT);
        $assetTypeStr = str_pad((string)$assetType, $pt['typeDigits'], '0', STR_PAD_LEFT);

        $urn = sprintf('urn:epc:id:grai:%s.%s.%s', $companyPrefixStr, $assetTypeStr, (string)$serial);

        return [
            'scheme' => 'grai-96',
            'header' => $header,
            'filter' => $filter,
            'partition' => $partition,
            'company_prefix' => $companyPrefixStr,
            'asset_type' => $assetTypeStr,
            'serial' => (string)$serial,
            'urn' => $urn,
        ];
    }
}
