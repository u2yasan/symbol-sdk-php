<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\ValueObjects;

readonly class MosaicId
{
    public \GMP $id;

    public function __construct(int|string|\GMP $mosaicId)
    {
        $this->id = match(true) {
            \is_int($mosaicId) => gmp_init($mosaicId),
            \is_string($mosaicId) => $this->parseString($mosaicId),
            $mosaicId instanceof \GMP => $mosaicId,
            default => throw new \InvalidArgumentException('Invalid mosaic ID type'),
        };

        if (gmp_cmp($this->id, 0) <= 0) {
            throw new \InvalidArgumentException('Mosaic ID must be positive');
        }
    }

    private function parseString(string $id): \GMP
    {
        return match(true) {
            str_starts_with($id, '0x') || str_starts_with($id, '0X') => gmp_init($id, 16),
            ctype_digit($id) => gmp_init($id, 10),
            ctype_xdigit($id) && \strlen($id) === 16 => gmp_init($id, 16),
            default => throw new \InvalidArgumentException('Invalid mosaic ID string format'),
        };
    }

    public static function fromHex(string $hex): self
    {
        return new self('0x' . ltrim($hex, '0x'));
    }

    public function toString(): string
    {
        return gmp_strval($this->id);
    }

    public function toHex(): string
    {
        $hex = gmp_strval($this->id, 16);
        return strtoupper(str_pad($hex, 16, '0', STR_PAD_LEFT));
    }

    public function toInt(): int
    {
        return gmp_intval($this->id);
    }

    public function equals(self $other): bool
    {
        return gmp_cmp($this->id, $other->id) === 0;
    }
}
