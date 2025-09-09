<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Models;

use SymbolSdk\Symbol\Enums\{NetworkType, TransactionType};
use SymbolSdk\Symbol\ValueObjects\{Amount, Timestamp, Hash};
use SymbolSdk\CryptoTypes\{PublicKey, Signature};

abstract readonly class Transaction
{
    public function __construct(
        public NetworkType $network,
        public TransactionType $type,
        public PublicKey $signerPublicKey,
        public Timestamp $deadline,
        public Amount $fee = new Amount(0),
        public ?Signature $signature = null,
        public int $version = 1,
    ) {}

    abstract public function serialize(): string;
    abstract public function getSize(): int;

    final public function calculateHash(): Hash
    {
        $serialized = $this->serialize();
        $payloadData = hex2bin($serialized);
        if ($payloadData === false) {
            throw new \RuntimeException('Failed to convert serialized transaction to binary');
        }
        
        $hash = hash('sha3-256', $payloadData, true);
        return Hash::fromBytes($hash);
    }

    final public function getSigningPayload(): string
    {
        $serialized = $this->serialize();
        
        // Skip size (4 bytes), reserved (4 bytes), signature (64 bytes), and signer (32 bytes)
        // Start from byte 104 (size + reserved + signature + signer = 4 + 4 + 64 + 32 = 104)
        $headerSize = 8 + 64 + 64; // 8 bytes header + 64 bytes signature + 64 bytes signer (hex)
        
        if (strlen($serialized) <= $headerSize) {
            throw new \InvalidArgumentException('Transaction too short to extract signing payload');
        }
        
        return substr($serialized, $headerSize);
    }

    public function isSigned(): bool
    {
        return $this->signature !== null && !$this->signature->isEmpty();
    }

    public function getTransactionInfo(): array
    {
        return [
            'type' => $this->type->getName(),
            'network' => $this->network->getName(),
            'version' => $this->version,
            'deadline' => $this->deadline->format(),
            'fee' => $this->fee->toString(),
            'size' => $this->getSize(),
            'signed' => $this->isSigned(),
            'hash' => $this->isSigned() ? $this->calculateHash()->toString() : null,
        ];
    }
}
