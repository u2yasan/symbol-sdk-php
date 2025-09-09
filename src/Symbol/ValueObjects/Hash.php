<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\ValueObjects;

readonly class Hash
{
    public string $value;

    public function __construct(string $hash)
    {
        $this->value = $this->validateAndNormalize($hash);
    }

    public static function fromHex(string $hex): self
    {
        return new self($hex);
    }

    public static function fromBytes(string $bytes): self
    {
        if (\strlen($bytes) !== 32) {
            throw new \InvalidArgumentException('Hash bytes must be exactly 32 bytes');
        }
        return new self(bin2hex($bytes));
    }

    public static function calculate(string $data): self
    {
        return new self(hash('sha3-256', $data));
    }

    private function validateAndNormalize(string $hash): string
    {
        $normalized = match(true) {
            \strlen($hash) === 64 && ctype_xdigit($hash) => strtoupper($hash),
            \strlen($hash) === 66 && str_starts_with($hash, '0x') => strtoupper(substr($hash, 2)),
            default => throw new \InvalidArgumentException(
                'Invalid hash format. Expected 64 hex characters or 32 bytes'
            ),
        };

        return $normalized;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toBytes(): string
    {
        $bytes = hex2bin($this->value);
        if ($bytes === false) {
            throw new \RuntimeException('Failed to convert hash to bytes');
        }
        return $bytes;
    }

    public function toHex(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === str_repeat('0', 64);
    }
}
