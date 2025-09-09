<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Models;

use SymbolSdk\CryptoTypes\{PublicKey, Signature};
use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\Symbol\ValueObjects\Address;

readonly class PublicAccount
{
    public function __construct(
        public PublicKey $publicKey,
        public Address $address,
        public NetworkType $networkType,
    ) {
    }

    public static function createFromPublicKey(PublicKey $publicKey, NetworkType $networkType): self
    {
        return $publicKey->createPublicAccount($networkType);
    }

    public function verify(string $data, Signature $signature): bool
    {
        return $this->publicKey->verify($data, $signature);
    }

    public function verifyTransaction(Transaction $transaction, Signature $signature): bool
    {
        $payload = $transaction->getSigningPayload();
        return $this->publicKey->verify($payload, $signature);
    }

    public function equals(self $other): bool
    {
        return $this->publicKey->equals($other->publicKey) &&
               $this->networkType === $other->networkType;
    }

    public function getShortAddress(int $length = 8): string
    {
        $addr = $this->address->toString();
        return substr($addr, 0, $length) . '...' . substr($addr, -$length);
    }
}
