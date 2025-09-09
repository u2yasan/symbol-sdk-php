<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Models;

use SymbolSdk\CryptoTypes\{PrivateKey, PublicKey, Signature};
use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\Symbol\ValueObjects\Address;

readonly class Account
{
    public function __construct(
        public PrivateKey $privateKey,
        public PublicKey $publicKey,
        public Address $address,
        public NetworkType $networkType,
    ) {
    }

    public static function createRandom(NetworkType $networkType): self
    {
        $privateKey = PrivateKey::random();
        return $privateKey->createAccount($networkType);
    }

    public static function createFromPrivateKey(PrivateKey $privateKey, NetworkType $networkType): self
    {
        return $privateKey->createAccount($networkType);
    }

    public static function createFromMnemonic(string $mnemonic, NetworkType $networkType, string $passphrase = ''): self
    {
        $privateKey = PrivateKey::fromMnemonic($mnemonic, $passphrase);
        return $privateKey->createAccount($networkType);
    }

    public function sign(string $data): Signature
    {
        return $this->privateKey->sign($data);
    }

    public function signTransaction(Transaction $transaction): Signature
    {
        $payload = $transaction->getSigningPayload();
        return $this->privateKey->sign($payload);
    }

    public function toPublicAccount(): PublicAccount
    {
        return new PublicAccount($this->publicKey, $this->address, $this->networkType);
    }

    public function equals(self $other): bool
    {
        return $this->privateKey->equals($other->privateKey) &&
               $this->networkType === $other->networkType;
    }
}
