<?php
namespace Epc\Schemes;

use Epc\Utils\BitReader;

/**
 * Decoder for GID-96 (EPC header 0x35).
 */
class GID96
{
    private const HEADER = 0x35;

    public static function decodeRaw(string $bytes): array
    {
        if (strlen($bytes) !== 12) {
            throw new \InvalidArgumentException("GID-96 requires exactly 12 bytes (96 bits)");
        }

        $r = new BitReader($bytes);
        $header = $r->readBits(8);
        if ($header !== self::HEADER) {
            throw new \InvalidArgumentException(sprintf("Header mismatch: expected 0x%02X got 0x%02X", self::HEADER, $header));
        }

        $gm = $r->readBits(28);
        $objectClass = $r->readBits(24);
        $serial = $r->readBits(36);

        return [
            'scheme' => 'gid-96',
            'header' => $header,
            'general_manager' => (string)$gm,
            'object_class' => (string)$objectClass,
            'serial' => (string)$serial,
        ];
    }
}
