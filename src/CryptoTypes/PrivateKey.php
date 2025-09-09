<?php

declare(strict_types=1);

namespace SymbolSdk\CryptoTypes;

use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\Symbol\ValueObjects\Address;
use SymbolSdk\Symbol\Models\Account;

readonly class PrivateKey
{
    public string $key;

    public function __construct(string $privateKey)
    {
        $this->key = $this->validateAndNormalize($privateKey);
    }

    public static function random(): self
    {
        // Generate random 32-byte private key using sodium
        $randomBytes = \sodium_randombytes_buf(32);
        return new self(bin2hex($randomBytes));
    }

    public static function fromSeed(string $seed): self
    {
        if (strlen($seed) < 16) {
            throw new \InvalidArgumentException('Seed must be at least 16 characters long');
        }
        
        // Use SHA3-256 to derive private key from seed
        $hash = hash('sha3-256', $seed, true);
        return new self(bin2hex($hash));
    }

    public static function fromMnemonic(string $mnemonic, string $passphrase = ''): self
    {
        // Simple mnemonic to private key derivation (BIP39-like)
        $seed = hash_pbkdf2('sha512', $mnemonic, 'mnemonic' . $passphrase, 2048, 64, true);
        return new self(bin2hex(substr($seed, 0, 32)));
    }

    private function validateAndNormalize(string $key): string
    {
        $normalized = match(true) {
            strlen($key) === 64 && ctype_xdigit($key) => strtoupper($key),
            strlen($key) === 66 && str_starts_with($key, '0x') => strtoupper(substr($key, 2)),
            strlen($key) === 66 && str_starts_with($key, '0X') => strtoupper(substr($key, 2)),
            default => throw new \InvalidArgumentException(
                'Invalid private key format. Expected 64 hex characters'
            ),
        };

        // Validate it's a valid 32-byte key
        if (strlen($normalized) !== 64) {
            throw new \InvalidArgumentException('Private key must be exactly 32 bytes (64 hex chars)');
        }

        // Ensure it's not zero
        if ($normalized === str_repeat('0', 64)) {
            throw new \InvalidArgumentException('Private key cannot be zero');
        }

        return $normalized;
    }

    public function derivePublicKey(): PublicKey
    {
        $privateKeyBytes = $this->toBytes();
        
        // Generate keypair using sodium and extract public key
        $keyPair = \sodium_crypto_sign_seed_keypair($privateKeyBytes);
        $publicKeyBytes = \sodium_crypto_sign_publickey($keyPair);
        
        return new PublicKey(bin2hex($publicKeyBytes));
    }

    public function sign(string $data): Signature
    {
        $privateKeyBytes = $this->toBytes();
        
        // Create keypair for signing
        $keyPair = \sodium_crypto_sign_seed_keypair($privateKeyBytes);
        
        // Sign the data
        $signature = \sodium_crypto_sign_detached($data, $keyPair);
        
        return new Signature(bin2hex($signature));
    }

    public function signTransaction(string $transactionPayload): Signature
    {
        // For Symbol transactions, we sign the payload directly
        return $this->sign($transactionPayload);
    }

    public function createAccount(NetworkType $networkType): Account
    {
        $publicKey = $this->derivePublicKey();
        $address = Address::createFromPublicKey($publicKey, $networkType);
        
        return new Account($this, $publicKey, $address, $networkType);
    }

    public function toString(): string
    {
        return $this->key;
    }

    public function toBytes(): string
    {
        $bytes = hex2bin($this->key);
        if ($bytes === false) {
            throw new \RuntimeException('Failed to convert private key to bytes');
        }
        return $bytes;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->key, $other->key);
    }
}
