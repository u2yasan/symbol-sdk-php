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
        if (\strlen($bytes) !== 64) {
            throw new \InvalidArgumentException('Signature bytes must be exactly 64 bytes');
        }
        return new self(bin2hex($bytes));
    }

    private function validateAndNormalize(string $signature): string
    {
        $normalized = match(true) {
            \strlen($signature) === 128 && ctype_xdigit($signature) => strtoupper($signature),
            \strlen($signature) === 130 && str_starts_with($signature, '0x') => strtoupper(substr($signature, 2)),
            \strlen($signature) === 130 && str_starts_with($signature, '0X') => strtoupper(substr($signature, 2)),
            default => throw new \InvalidArgumentException(
                'Invalid signature format. Expected 128 hex characters (64 bytes)'
            ),
        };

        if (\strlen($normalized) !== 128) {
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

    /**
     * Split signature into R and S components (Ed25519)
     */
    public function getComponents(): array
    {
        $r = substr($this->value, 0, 64);  // First 32 bytes
        $s = substr($this->value, 64, 64); // Last 32 bytes

        return ['r' => $r, 's' => $s];
    }

    /**
     * Get a short representation of the signature for display
     */
    public function getShortString(int $length = 8): string
    {
        if ($length < 4 || $length > 32) {
            throw new \InvalidArgumentException('Length must be between 4 and 32');
        }

        return substr($this->value, 0, $length) . '...' . substr($this->value, -$length);
    }
}
