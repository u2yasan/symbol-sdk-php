<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol;

use Error;
use SymbolSdk\CryptoTypes\Signature;
use SymbolSdk\Impl\Ed25519;
use SymbolSdk\Symbol\Models\PublicKey;
use SymbolSdk\Utils\ArrayHelpers;

/**
 * Verifies signatures signed by a single key pair.
 */
class Verifier
{
    public const HASH_MODE = 'sha512';
    public PublicKey $publicKey;

    /**
     * Creates a verifier from a public key.
     * @param PublicKey $publicKey Public key.
     */
    public function __construct(PublicKey $publicKey)
    {
        // Models\PublicKeyからバイトデータを取得
        $publicKeyBytes = match(true) {
            method_exists($publicKey, 'toBytes') => $publicKey->toBytes(),
            property_exists($publicKey, 'binaryData') => $publicKey->binaryData,
            default => (string)$publicKey,
        };
        
        if (0 === ArrayHelpers::deepCompare(str_repeat("\x00", 32), $publicKeyBytes)) {
            throw new Error('public key cannot be zero');
        }
        $this->publicKey = $publicKey;
    }

    /**
     * Verifies a message signature.
     * @param string $message Message to verify.
     * @param Signature $signature Signature to verify.
     * @return bool true if the message signature verifies.
     */
    public function verify(string $message, Signature $signature): bool
    {
        // CryptoTypes\Signatureは toBytes() メソッドを持っている
        $signatureBytes = $signature->toBytes();
        
        // Models\PublicKeyからバイトデータを取得
        $publicKeyBytes = match(true) {
            method_exists($this->publicKey, 'toBytes') => $this->publicKey->toBytes(),
            property_exists($this->publicKey, 'binaryData') => $this->publicKey->binaryData,
            default => (string)$this->publicKey,
        };
        
        return Ed25519::verify($message, $signatureBytes, $publicKeyBytes, self::HASH_MODE);
    }
}