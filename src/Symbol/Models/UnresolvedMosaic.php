<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Models;

use SymbolSdk\Symbol\ValueObjects\{Amount, MosaicId};

readonly class UnresolvedMosaic
{
    public function __construct(
        public MosaicId $mosaicId,
        public Amount $amount,
    ) {
    }

    public function serialize(): string
    {
        $buffer = '';

        // Mosaic ID (8 bytes, little-endian)
        $mosaicIdValue = $this->mosaicId->toInt();
        $buffer .= pack('P', $mosaicIdValue);

        // Amount (8 bytes, little-endian)
        $amountValue = $this->amount->toInt();
        $buffer .= pack('P', $amountValue);

        return bin2hex($buffer);
    }

    public function getSize(): int
    {
        return 16; // 8 bytes mosaic ID + 8 bytes amount
    }

    public static function fromSerialized(string $data): self
    {
        if (\strlen($data) !== 32) { // 16 bytes = 32 hex chars
            throw new \InvalidArgumentException('Invalid mosaic data length');
        }

        $binary = hex2bin($data);
        if ($binary === false) {
            throw new \InvalidArgumentException('Invalid hex data');
        }

        $unpacked = unpack('Pmosaic_id/Pamount', $binary);
        if ($unpacked === false) {
            throw new \InvalidArgumentException('Failed to unpack mosaic data');
        }

        return new self(
            new MosaicId($unpacked['mosaic_id']),
            new Amount($unpacked['amount'])
        );
    }
}
