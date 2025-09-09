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

    /**
     * Complete transfer workflow matching the test expectations
     * 
     * @param Account $sender The sender account
     * @param string $recipientAddress The recipient address as string
     * @param float $xymAmount Amount in XYM
     * @param string $message Transaction message
     * @param int $feeMultiplier Fee multiplier
     * @return array Workflow data with transaction, announcementData, and summary
     */
    public function buildTransferWorkflow(
        Account $sender,
        string $recipientAddress,
        float $xymAmount,
        string $message = '',
        int $feeMultiplier = 100
    ): array {
        // Validate non-negative amount
        if ($xymAmount < 0) {
            throw new \InvalidArgumentException(
                sprintf('Amount cannot be negative. Got: %f', $xymAmount)
            );
        }

        // Parse recipient address
        $recipientAddr = $this->parseAddress($recipientAddress);
        
        // Create amount from XYM
        $amount = $this->createAmount($xymAmount, true);
        
        // Create transfer transaction
        $transaction = $this->createXymTransfer(
            signerPublicKey: $sender->publicKey,
            recipientAddress: $recipientAddr,
            amount: $amount,
            message: $message,
            deadline: $this->createTimestamp(7200) // 2 hours deadline
        );

        // Set max fee
        $transactionWithFee = $this->setMaxFee($transaction, $feeMultiplier);

        // Sign transaction
        $signedTransaction = $this->signTransaction($transactionWithFee, $sender);

        // Get announcement data
        $announcementData = $this->announceTransaction($signedTransaction);

        // Format amount string
        $formattedAmount = number_format($xymAmount, 2) . ' XYM';

        // Create summary
        $summary = [
            'from' => $sender->address->toString(),
            'to' => $recipientAddress,
            'amount' => $formattedAmount,
            'message' => $message,
            'fee' => $announcementData['fee'],
            'hash' => $announcementData['hash'],
        ];

        return [
            'transaction' => $signedTransaction,
            'announcementData' => $announcementData,
            'summary' => $summary,
        ];
    }

    /**
     * Simplified transfer workflow for XYM transfers
     *
     * @param Account $senderAccount
     * @param string $recipientAddress
     * @param float $xymAmount Amount in XYM
     * @param string $message
     * @return array
     */
    public function buildXymTransferWorkflow(
        Account $senderAccount,
        string $recipientAddress,
        float $xymAmount,
        string $message = ''
    ): array {
        return $this->buildTransferWorkflow(
            senderAccount: $senderAccount,
            recipientAddress: $recipientAddress,
            amount: $xymAmount,
            message: $message,
            isAmountInXym: true
        );
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