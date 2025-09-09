<?php

declare(strict_types=1);

namespace SymbolSdk\Facade;

use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\Symbol\Models\{Account, PublicAccount, TransferTransaction};
use SymbolSdk\Symbol\ValueObjects\{Amount, Timestamp, Address};
use SymbolSdk\CryptoTypes\{PrivateKey, PublicKey, Signature};
use SymbolSdk\Utils\{TransactionFactory, NodeHealthChecker};

final readonly class SymbolFacade
{
    public NetworkConfig $config;

    public function __construct(NetworkType|string $network, ?array $customNodes = null)
    {
        $networkType = match(true) {
            $network instanceof NetworkType => $network,
            is_string($network) => NetworkType::fromName($network),
            default => throw new \InvalidArgumentException('Invalid network type'),
        };

        $this->config = new NetworkConfig($networkType, $customNodes);
    }

    // === Account Management ===

    public function createAccount(?PrivateKey $privateKey = null): Account
    {
        $privateKey ??= PrivateKey::random();
        return $privateKey->createAccount($this->config->networkType);
    }

    public function createAccountFromMnemonic(string $mnemonic, string $passphrase = ''): Account
    {
        $privateKey = PrivateKey::fromMnemonic($mnemonic, $passphrase);
        return $this->createAccount($privateKey);
    }

    public function createPublicAccount(PublicKey $publicKey): PublicAccount
    {
        return $publicKey->createPublicAccount($this->config->networkType);
    }

    // === Transaction Creation ===

    public function createTransfer(
        PublicKey $signerPublicKey,
        Address $recipientAddress,
        array $mosaics = [],
        string $message = '',
        ?Timestamp $deadline = null
    ): TransferTransaction {
        return TransactionFactory::createTransfer(
            network: $this->config->networkType,
            signerPublicKey: $signerPublicKey,
            recipientAddress: $recipientAddress,
            mosaics: $mosaics,
            message: $message,
            deadline: $deadline
        );
    }

    public function createXymTransfer(
        PublicKey $signerPublicKey,
        Address $recipientAddress,
        Amount $amount,
        string $message = '',
        ?Timestamp $deadline = null
    ): TransferTransaction {
        return TransactionFactory::createXymTransfer(
            network: $this->config->networkType,
            signerPublicKey: $signerPublicKey,
            recipientAddress: $recipientAddress,
            amount: $amount,
            message: $message,
            deadline: $deadline
        );
    }

    // === Transaction Management ===

    public function signTransaction(TransferTransaction $transaction, Account $signer): TransferTransaction
    {
        $signature = $signer->signTransaction($transaction);
        return $transaction->withSignature($signature);
    }

    public function setMaxFee(TransferTransaction $transaction, int $feeMultiplier = 100): TransferTransaction
    {
        return TransactionFactory::setMaxFee($transaction, $feeMultiplier);
    }

    public function announceTransaction(TransferTransaction $signedTransaction): array
    {
        if (!$signedTransaction->isSigned()) {
            throw new \InvalidArgumentException('Transaction must be signed before announcing');
        }

        return [
            'payload' => $signedTransaction->serialize(),
            'hash' => $signedTransaction->calculateHash()->toString(),
            'signerPublicKey' => $signedTransaction->signerPublicKey->toString(),
            'type' => $signedTransaction->type->getName(),
            'network' => $this->config->networkType->getName(),
            'fee' => $signedTransaction->fee->toString(),
            'deadline' => $signedTransaction->deadline->format(),
        ];
    }

    // === Utility Methods ===

    public function now(): Timestamp
    {
        return Timestamp::now();
    }

    public function createTimestamp(?int $secondsFromNow = null): Timestamp
    {
        if ($secondsFromNow === null) {
            return $this->now();
        }
        return $this->now()->addSeconds($secondsFromNow);
    }

    public function getNodeHealthChecker(): NodeHealthChecker
    {
        return new NodeHealthChecker();
    }

    public function getHealthyNodes(): array
    {
        return $this->config->getHealthyNodes();
    }

    public function getBestNode(): string
    {
        return $this->config->getBestNode();
    }

    // === Convenience Methods ===

    public function createRandomAccount(): Account
    {
        return $this->createAccount();
    }

    public function parseAddress(string $address): Address
    {
        return new Address($address);
    }

    public function createAmount(int|float|string $amount, bool $isXym = true): Amount
    {
        if ($isXym && is_numeric($amount)) {
            return Amount::fromXym((float) $amount);
        }
        return new Amount($amount);
    }

    // === Information Methods ===

    public function getNetworkInfo(): array
    {
        return [
            'networkType' => $this->config->networkType->getName(),
            'networkValue' => $this->config->networkType->value,
            'generationHashSeed' => $this->config->generationHashSeed,
            'currencyMosaicId' => $this->config->currencyMosaicId->toHex(),
            'epochAdjustment' => $this->config->epochAdjustment,
            'configuredNodes' => count($this->config->nodes),
            'recommendedNodes' => $this->config->nodes,
        ];
    }

    public function validateAddress(string $address): bool
    {
        try {
            new Address($address);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
