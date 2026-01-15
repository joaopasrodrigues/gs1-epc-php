<?php
namespace Epc;

use Epc\Schemes\SGTIN96;
use Epc\Schemes\SSCC96;
use Epc\Schemes\SGLN96;
use Epc\Schemes\GRAI96;
use Epc\Schemes\GIAI96;
use Epc\Schemes\GID96;

/**
 * High-level Decoder for EPC values.
 *
 * Recognizes multiple 96-bit EPC schemes and basic URN parsing for several schemes.
 */
class Decoder
{
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

    public function decodeBytes(string $bytes): array
    {
        $len = strlen($bytes);
        if ($len === 12) {
            $header = ord($bytes[0]);
            switch ($header) {
                case 0x30:
                    return SGTIN96::decodeRaw($bytes);
                case 0x31:
                    return SSCC96::decodeRaw($bytes);
                case 0x32:
                    return SGLN96::decodeRaw($bytes);
                case 0x33:
                    return GRAI96::decodeRaw($bytes);
                case 0x34:
                    return GIAI96::decodeRaw($bytes);
                case 0x35:
                    return GID96::decodeRaw($bytes);
                default:
                    throw new \InvalidArgumentException(sprintf("Unsupported 96-bit EPC header: 0x%02X", $header));
            }
        } else {
            throw new \InvalidArgumentException("Unsupported EPC length: {$len} bytes");
        }
    }

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
        } elseif (stripos($urn, 'urn:epc:id:grai:') === 0) {
            $rest = substr($urn, strlen('urn:epc:id:grai:'));
            $parts = explode('.', $rest);
            if (count($parts) !== 3) {
                throw new \InvalidArgumentException("Invalid GRAI URN format");
            }
            [$cp, $atype, $serial] = $parts;
            return [
                'scheme' => 'grai-urn',
                'company_prefix' => $cp,
                'asset_type' => $atype,
                'serial' => $serial,
                'urn' => $urn,
            ];
        } elseif (stripos($urn, 'urn:epc:id:giai:') === 0) {
            $rest = substr($urn, strlen('urn:epc:id:giai:'));
            $parts = explode('.', $rest);
            if (count($parts) !== 3) {
                throw new \InvalidArgumentException("Invalid GIAI URN format");
            }
            [$cp, $ref, $serial] = $parts;
            return [
                'scheme' => 'giai-urn',
                'company_prefix' => $cp,
                'reference' => $ref,
                'serial' => $serial,
                'urn' => $urn,
            ];
        } elseif (stripos($urn, 'urn:epc:id:sgln:') === 0) {
            $rest = substr($urn, strlen('urn:epc:id:sgln:'));
            $parts = explode('.', $rest);
            if (count($parts) !== 3) {
                throw new \InvalidArgumentException("Invalid SGLN URN format");
            }
            [$cp, $loc, $ext] = $parts;
            return [
                'scheme' => 'sgln-urn',
                'company_prefix' => $cp,
                'location_reference' => $loc,
                'extension' => $ext,
                'urn' => $urn,
            ];
        }

        throw new \InvalidArgumentException("Unsupported URN scheme");
    }
}
