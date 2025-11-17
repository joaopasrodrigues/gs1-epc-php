<?php
namespace Epc;

use Epc\Schemes\SGTIN96;

/**
 * High-level Decoder for EPC values.
 *
 * Supports:
 * - decodeHex: decode hex-encoded EPC (e.g., 96-bit hex for SGTIN-96)
 * - decodeBytes: decode raw binary string
 * - decodeUrn: parse an EPC URN (basic, supports sgtin URNs)
 */
class Decoder
{
    /**
     * Decode an EPC provided as a hex string (with or without 0x, case-insensitive).
     *
     * @param string $hex
     * @return array
     */
    public function decodeHex(string $hex): array
    {
        $hex = preg_replace('/^0x/i', '', trim($hex));
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
        $bytes = hex2bin($hex);
        if ($bytes === false) {
            throw new \InvalidArgumentException("Invalid hex input");
        }
        return $this->decodeBytes($bytes);
    }

    /**
     * Decode raw bytes.
     *
     * @param string $bytes
     * @return array
     */
    public function decodeBytes(string $bytes): array
    {
        $len = strlen($bytes);
        if ($len === 12) {
            // 96-bit tag family; peek header byte
            $header = ord($bytes[0]);
            switch ($header) {
                case 0x30:
                    return SGTIN96::decodeRaw($bytes);
                default:
                    throw new \InvalidArgumentException(sprintf("Unsupported 96-bit EPC header: 0x%02X", $header));
            }
        } else {
            throw new \InvalidArgumentException("Unsupported EPC length: {$len} bytes");
        }
    }

    /**
     * Parse a simple EPC URN (urn:epc:id:sgtin:cp.item.serial).
     *
     * @param string $urn
     * @return array
     */
    public function decodeUrn(string $urn): array
    {
        $urn = trim($urn);
        if (stripos($urn, 'urn:epc:id:sgtin:') === 0) {
            $rest = substr($urn, strlen('urn:epc:id:sgtin:'));
            $parts = explode('.', $rest);
            if (count($parts) !== 3) {
                throw new \InvalidArgumentException("Invalid SGTIN URN format");
            }
            [$cp, $item, $serial] = $parts;
            // Build gtin13 from cp + item by left-padding item to fit 13 total digits
            $cp = preg_replace('/[^0-9]/', '', $cp);
            $item = preg_replace('/[^0-9]/', '', $item);
            $combined = str_pad($cp, max(0, 13 - strlen($item)), '0', STR_PAD_LEFT) . $item;
            return [
                'scheme' => 'sgtin-urn',
                'company_prefix' => $cp,
                'item_reference' => $item,
                'serial' => $serial,
                'urn' => $urn,
                'gtin14' => SGTIN96::gtin14FromGtin13($combined),
            ];
        }
        throw new \InvalidArgumentException("Unsupported URN scheme");
    }
}