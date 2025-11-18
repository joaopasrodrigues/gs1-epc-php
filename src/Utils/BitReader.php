<?php
namespace Epc\Utils;

/**
 * Simple bit reader for big-endian bit streams.
 *
 * Created for reading EPC fields from an array of bytes (uint8).
 */
class BitReader
{
    private string $data;
    private int $bitPos = 0; // next bit to read (0..n-1)

    /**
     * @param string $bytes raw bytes string
     */
    public function __construct(string $bytes)
    {
        $this->data = $bytes;
        $this->bitPos = 0;
    }

    /**
     * Read n bits (n <= 64) and return as integer.
     * Bits are read MSB-first.
     *
     * @param int $n
     * @return int
     * @throws \InvalidArgumentException
     */
    public function readBits(int $n): int
    {
        if ($n < 1 || $n > 64) {
            throw new \InvalidArgumentException("readBits supports 1..64 bits, requested {$n}");
        }
        $available = strlen($this->data) * 8 - $this->bitPos;
        if ($n > $available) {
            throw new \InvalidArgumentException("Not enough bits to read: requested {$n}, available {$available}");
        }

        $value = 0;
        for ($i = 0; $i < $n; $i++) {
            $byteIndex = intdiv($this->bitPos, 8);
            $innerBit = 7 - ($this->bitPos % 8); // MSB first
            $byte = ord($this->data[$byteIndex]);
            $bit = ($byte >> $innerBit) & 1;
            $value = ($value << 1) | $bit;
            $this->bitPos++;
        }
        return $value;
    }

    public function skipBits(int $n): void
    {
        $this->bitPos += $n;
        if ($this->bitPos < 0) $this->bitPos = 0;
        $max = strlen($this->data) * 8;
        if ($this->bitPos > $max) $this->bitPos = $max;
    }

    public function bitsRemaining(): int
    {
        return strlen($this->data) * 8 - $this->bitPos;
    }
}
