<?php

declare(strict_types=1);

namespace SymbolSdk\CryptoTypes;

readonly class Signature
{
    public string $value;

    public function __construct(string $signature)
    {
        $this->value = $this->validateAndNormalize($signature);
    }

    public static function fromBytes(string $bytes): self
    {
        if (strlen($bytes) !== 64) {
            throw new \InvalidArgumentException('Signature bytes must be exactly 64 bytes');
        }
        return new self(bin2hex($bytes));
    }

    private function validateAndNormalize(string $signature): string
    {
        $normalized = match(true) {
            strlen($signature) === 128 && ctype_xdigit($signature) => strtoupper($signature),
            strlen($signature) === 130 && str_starts_with($signature, '0x') => strtoupper(substr($signature, 2)),
            strlen($signature) === 130 && str_starts_with($signature, '0X') => strtoupper(substr($signature, 2)),
            default => throw new \InvalidArgumentException(
                'Invalid signature format. Expected 128 hex characters (64 bytes)'
            ),
        };

        if (strlen($normalized) !== 128) {
            throw new \InvalidArgumentException('Signature must be exactly 64 bytes (128 hex chars)');
        }

        return $normalized;
    }

    public function verify(string $data, PublicKey $publicKey): bool
    {
        return $publicKey->verify($data, $this);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toBytes(): string
    {
        $bytes = hex2bin($this->value);
        if ($bytes === false) {
            throw new \RuntimeException('Failed to convert signature to bytes');
        }
        return $bytes;
    }

    public function toHex(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function isEmpty(): bool
    {
        return $this->value === str_repeat('0', 128);
    }
}
