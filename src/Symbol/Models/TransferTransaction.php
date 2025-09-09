<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Models;

use SymbolSdk\Symbol\Enums\{NetworkType, TransactionType};
use SymbolSdk\Symbol\ValueObjects\{Amount, Timestamp, Address};
use SymbolSdk\CryptoTypes\{PublicKey, Signature};

readonly class TransferTransaction extends Transaction
{
    public function __construct(
        NetworkType $network,
        PublicKey $signerPublicKey,
        Timestamp $deadline,
        public Address $recipientAddress,
        public array $mosaics = [],
        public string $message = '',
        Amount $fee = new Amount(0),
        ?Signature $signature = null,
        int $version = 1,
    ) {
        parent::__construct(
            network: $network,
            type: TransactionType::TRANSFER,
            signerPublicKey: $signerPublicKey,
            deadline: $deadline,
            fee: $fee,
            signature: $signature,
            version: $version
        );

        // Validate mosaics
        foreach ($mosaics as $mosaic) {
            if (!$mosaic instanceof UnresolvedMosaic) {
                throw new \InvalidArgumentException('All mosaics must be UnresolvedMosaic instances');
            }
        }
    }

    public function serialize(): string
    {
        $buffer = '';
        
        // Transaction header
        $buffer .= pack('V', $this->getSize());                    // Size (4 bytes)
        $buffer .= pack('V', 0);                                   // Reserved (4 bytes)
        $buffer .= $this->signature?->toBytes() ?? str_repeat("\0", 64); // Signature (64 bytes)
        $buffer .= $this->signerPublicKey->toBytes();              // Signer (32 bytes)
        $buffer .= pack('V', 0);                                   // Reserved (4 bytes)
        $buffer .= pack('C', $this->network->value);               // Network (1 byte)
        $buffer .= pack('v', $this->type->value);                  // Type (2 bytes)
        $buffer .= pack('P', $this->fee->toInt());                 // Fee (8 bytes)
        $buffer .= pack('P', $this->deadline->toInt());            // Deadline (8 bytes)
        
        // Transfer specific data
        $buffer .= $this->recipientAddress->toBytes();             // Recipient (25 bytes)
        
        // Message
        $messageBytes = $this->message;
        $messageLength = strlen($messageBytes);
        $buffer .= pack('v', $messageLength);                      // Message length (2 bytes)
        $buffer .= $messageBytes;                                  // Message content
        
        // Padding for message (align to 8 bytes)
        $messagePadding = (8 - (($messageLength + 2) % 8)) % 8;
        $buffer .= str_repeat("\0", $messagePadding);
        
        // Mosaics
        $buffer .= pack('C', count($this->mosaics));               // Mosaic count (1 byte)
        $buffer .= str_repeat("\0", 7);                            // Reserved (7 bytes)
        
        foreach ($this->mosaics as $mosaic) {
            $buffer .= hex2bin($mosaic->serialize());
        }
        
        return bin2hex($buffer);
    }

    public function getSize(): int
    {
        $baseSize = 4 + 4 + 64 + 32 + 4 + 1 + 2 + 8 + 8;      // Header: 127 bytes
        $addressSize = 25;                                        // Recipient address: 25 bytes
        $messageSize = 2 + strlen($this->message);                // Message length + content
        $messagePadding = (8 - (($messageSize) % 8)) % 8;       // Padding to align to 8 bytes
        $mosaicHeaderSize = 1 + 7;                               // Mosaic count + reserved: 8 bytes
        $mosaicDataSize = count($this->mosaics) * 16;            // Each mosaic: 16 bytes
        
        return $baseSize + $addressSize + $messageSize + $messagePadding + $mosaicHeaderSize + $mosaicDataSize;
    }

    public function withSignature(Signature $signature): self
    {
        return new self(
            network: $this->network,
            signerPublicKey: $this->signerPublicKey,
            deadline: $this->deadline,
            recipientAddress: $this->recipientAddress,
            mosaics: $this->mosaics,
            message: $this->message,
            fee: $this->fee,
            signature: $signature,
            version: $this->version
        );
    }

    public function withFee(Amount $fee): self
    {
        return new self(
            network: $this->network,
            signerPublicKey: $this->signerPublicKey,
            deadline: $this->deadline,
            recipientAddress: $this->recipientAddress,
            mosaics: $this->mosaics,
            message: $this->message,
            fee: $fee,
            signature: $this->signature,
            version: $this->version
        );
    }

    public function withMessage(string $message): self
    {
        return new self(
            network: $this->network,
            signerPublicKey: $this->signerPublicKey,
            deadline: $this->deadline,
            recipientAddress: $this->recipientAddress,
            mosaics: $this->mosaics,
            message: $message,
            fee: $this->fee,
            signature: $this->signature,
            version: $this->version
        );
    }

    public function withMosaics(array $mosaics): self
    {
        return new self(
            network: $this->network,
            signerPublicKey: $this->signerPublicKey,
            deadline: $this->deadline,
            recipientAddress: $this->recipientAddress,
            mosaics: $mosaics,
            message: $this->message,
            fee: $this->fee,
            signature: $this->signature,
            version: $this->version
        );
    }

    public function addMosaic(UnresolvedMosaic $mosaic): self
    {
        $newMosaics = [...$this->mosaics, $mosaic];
        return $this->withMosaics($newMosaics);
    }

    public function getTotalValue(): Amount
    {
        return array_reduce(
            $this->mosaics,
            static fn(Amount $carry, UnresolvedMosaic $mosaic): Amount => $carry->add($mosaic->amount),
            Amount::zero()
        );
    }
}
