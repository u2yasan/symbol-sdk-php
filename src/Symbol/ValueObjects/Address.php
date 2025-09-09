<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\ValueObjects;

use SymbolSdk\CryptoTypes\PublicKey;
use SymbolSdk\Symbol\Enums\NetworkType;

readonly class Address
{
    public string $value;

    public function __construct(string $address)
    {
        $this->value = $this->validateAndNormalize($address);
    }

    public static function createFromPublicKey(PublicKey $publicKey, NetworkType $networkType): self
    {
        // Symbol address derivation from public key
        $publicKeyBytes = $publicKey->toBytes();
        $sha3Hash = hash('sha3-256', $publicKeyBytes, true);
        $ripemdHash = hash('ripemd160', $sha3Hash, true);

        // Add network type byte
        $addressBytes = \chr($networkType->value) . $ripemdHash;

        // Calculate checksum
        $checksum = substr(hash('sha3-256', $addressBytes, true), 0, 3);
        $fullAddress = $addressBytes . $checksum;

        // Convert to base32 (Symbol uses custom alphabet)
        return new self(self::encodeBase32($fullAddress));
    }

    private function validateAndNormalize(string $address): string
    {
        // Remove dashes and convert to uppercase
        $normalized = strtoupper(str_replace('-', '', trim($address)));

        if (\strlen($normalized) !== 39) {
            throw new \InvalidArgumentException('Address must be 39 characters long');
        }

        if (!preg_match('/^[A-Z2-7]{39}$/', $normalized)) {
            throw new \InvalidArgumentException('Address contains invalid characters');
        }

        // Validate network type (first character)
        $networkByte = $this->decodeNetworkByte($normalized[0]);
        if (!\in_array($networkByte, [NetworkType::MAINNET->value, NetworkType::TESTNET->value], true)) {
            throw new \InvalidArgumentException('Invalid network type in address');
        }

        return $normalized;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toFormattedString(): string
    {
        // Add dashes for better readability: XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXX
        return chunk_split($this->value, 6, '-');
    }

    public function getNetworkType(): NetworkType
    {
        $networkByte = $this->decodeNetworkByte($this->value[0]);
        return NetworkType::from($networkByte);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function decodeNetworkByte(string $char): int
    {
        // Symbol base32 alphabet: ABCDEFGHIJKLMNOPQRSTUVWXYZ234567
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $pos = strpos($alphabet, $char);

        if ($pos === false) {
            throw new \InvalidArgumentException('Invalid base32 character');
        }

        return $pos;
    }

    private static function encodeBase32(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $byte) {
            $buffer = ($buffer << 8) | \ord($byte);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $encoded .= $alphabet[($buffer >> ($bitsLeft - 5)) & 31];
                $bitsLeft -= 5;
            }
        }

        if ($bitsLeft > 0) {
            $encoded .= $alphabet[($buffer << (5 - $bitsLeft)) & 31];
        }

        return $encoded;
    }
}
