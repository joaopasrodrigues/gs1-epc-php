<?php
namespace Epc\Schemes;

use Epc\Utils\BitReader;

/**
 * Decoder for SSCC-96 (EPC header 0x31).
 */
class SSCC96
{
    private const HEADER = 0x31;

    private static array $partitionTable = [
        0 => ['cpBits' => 40, 'refBits' => 21, 'cpDigits' => 12, 'refDigits' => 6],
        1 => ['cpBits' => 37, 'refBits' => 24, 'cpDigits' => 11, 'refDigits' => 7],
        2 => ['cpBits' => 34, 'refBits' => 27, 'cpDigits' => 10, 'refDigits' => 8],
        3 => ['cpBits' => 30, 'refBits' => 31, 'cpDigits' => 9,  'refDigits' => 9],
        4 => ['cpBits' => 27, 'refBits' => 34, 'cpDigits' => 8,  'refDigits' => 10],
        5 => ['cpBits' => 24, 'refBits' => 37, 'cpDigits' => 7,  'refDigits' => 11],
        6 => ['cpBits' => 20, 'refBits' => 41, 'cpDigits' => 6,  'refDigits' => 12],
    ];

    public static function decodeRaw(string $bytes): array
    {
        if (strlen($bytes) !== 12) {
            throw new \InvalidArgumentException("SSCC-96 requires exactly 12 bytes (96 bits)");
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
        $serialRef = $r->readBits($pt['refBits']);

        $companyPrefixStr = str_pad((string)$companyPrefix, $pt['cpDigits'], '0', STR_PAD_LEFT);
        $serialRefStr = str_pad((string)$serialRef, $pt['refDigits'], '0', STR_PAD_LEFT);

        $withoutCheck = '0' . $companyPrefixStr . $serialRefStr;
        if (strlen($withoutCheck) !== 17) {
            $withoutCheck = str_pad($withoutCheck, 17, '0', STR_PAD_LEFT);
        }
        $check = self::calculateCheckDigit($withoutCheck);
        $sscc18 = $withoutCheck . $check;

        $urn = sprintf('urn:epc:id:sscc:%s.%s', ltrim($companyPrefixStr, '0') === '' ? '0' : ltrim($companyPrefixStr, '0'), ltrim($serialRefStr, '0') === '' ? '0' : ltrim($serialRefStr, '0'));

        return [
            'scheme' => 'sscc-96',
            'header' => $header,
            'filter' => $filter,
            'partition' => $partition,
            'company_prefix' => $companyPrefixStr,
            'serial_reference' => $serialRefStr,
            'sscc' => $sscc18,
            'urn' => $urn,
        ];
    }

    private static function calculateCheckDigit(string $digits): string
    {
        $digits = preg_replace('/\D/', '', $digits);
        $sum = 0;
        $reversed = array_reverse(str_split($digits));
        foreach ($reversed as $i => $d) {
            $mult = ($i % 2 === 0) ? 3 : 1;
            $sum += intval($d) * $mult;
        }
        $mod = $sum % 10;
        $check = $mod === 0 ? 0 : (10 - $mod);
        return (string)$check;
    }
}
