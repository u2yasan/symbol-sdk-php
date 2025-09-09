<?php

declare(strict_types=1);

namespace SymbolSdk\Builder;

use SymbolSdk\Facade\SymbolFacade;
use SymbolSdk\Symbol\Models\{TransferTransaction, UnresolvedMosaic};
use SymbolSdk\Symbol\ValueObjects\{Amount, Timestamp, Address, MosaicId};
use SymbolSdk\CryptoTypes\PublicKey;

final class TransactionBuilder
{
    private ?PublicKey $signerPublicKey = null;
    private ?Address $recipientAddress = null;
    private array $mosaics = [];
    private string $message = '';
    private ?Timestamp $deadline = null;
    private ?Amount $fee = null;
    private int $feeMultiplier = 100;
    private bool $autoCalculateFee = false;

    public function __construct(
        private readonly SymbolFacade $facade
    ) {
    }

    public static function create(SymbolFacade $facade): self
    {
        return new self($facade);
    }

    public function signer(PublicKey $publicKey): self
    {
        $this->signerPublicKey = $publicKey;
        return $this;
    }

    public function to(Address|string $recipient): self
    {
        $this->recipientAddress = match(true) {
            $recipient instanceof Address => $recipient,
            is_string($recipient) => new Address($recipient),
            default => throw new \InvalidArgumentException('Invalid recipient type')
        };
        return $this;
    }

    public function amount(Amount|float|int $amount): self
    {
        // Validate non-negative amounts
        if (is_numeric($amount) && $amount < 0) {
            throw new \InvalidArgumentException(
                sprintf('Amount cannot be negative. Got: %s', $amount)
            );
        }

        $amountObj = match(true) {
            $amount instanceof Amount => $amount,
            is_numeric($amount) => Amount::fromXym((float) $amount),
            default => throw new \InvalidArgumentException('Invalid amount type')
        };

        $mosaic = new UnresolvedMosaic(
            $this->facade->config->currencyMosaicId,
            $amountObj
        );

        return $this->mosaic($mosaic);
    }

    public function mosaic(UnresolvedMosaic $mosaic): self
    {
        $this->mosaics[] = $mosaic;
        return $this;
    }

    public function mosaics(array $mosaics): self
    {
        $this->mosaics = array_merge($this->mosaics, $mosaics);
        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function deadline(Timestamp|int $deadline): self
    {
        $this->deadline = match(true) {
            $deadline instanceof Timestamp => $deadline,
            is_int($deadline) => $this->facade->createTimestamp($deadline),
            default => throw new \InvalidArgumentException('Invalid deadline type')
        };
        return $this;
    }

    public function fee(Amount|int $fee): self
    {
        $this->fee = match(true) {
            $fee instanceof Amount => $fee,
            is_int($fee) => new Amount($fee),
            default => throw new \InvalidArgumentException('Invalid fee type')
        };
        $this->autoCalculateFee = false;
        return $this;
    }

    public function autoFee(int $feeMultiplier = 100): self
    {
        if ($feeMultiplier < 0) {
            throw new \InvalidArgumentException('Fee multiplier cannot be negative');
        }
        
        $this->feeMultiplier = $feeMultiplier;
        $this->autoCalculateFee = true;
        $this->fee = null; // Clear any manually set fee
        return $this;
    }

    public function build(): TransferTransaction
    {
        $this->validate();

        $transaction = $this->facade->createTransfer(
            signerPublicKey: $this->signerPublicKey,
            recipientAddress: $this->recipientAddress,
            mosaics: $this->mosaics,
            message: $this->message,
            deadline: $this->deadline ?? $this->facade->createTimestamp(7200)
        );

        // Handle fee calculation
        if ($this->autoCalculateFee) {
            return $this->facade->setMaxFee($transaction, $this->feeMultiplier);
        } elseif ($this->fee !== null) {
            return $transaction->withFee($this->fee);
        } else {
            // Default auto fee if no fee specified
            return $this->facade->setMaxFee($transaction, 100);
        }
    }

    private function validate(): void
    {
        if ($this->signerPublicKey === null) {
            throw new \InvalidArgumentException('Signer public key is required');
        }

        if ($this->recipientAddress === null) {
            throw new \InvalidArgumentException('Recipient address is required');
        }
    }

    // === Preset Methods ===

    public static function transfer(SymbolFacade $facade): self
    {
        return new self($facade);
    }

    /**
     * Create XYM transfer with automatic fee calculation
     * 
     * @param SymbolFacade $facade
     * @param PublicKey $signer
     * @param string $recipient
     * @param float $amount Amount in XYM (must be positive)
     * @param string $message
     * @return self
     * @throws \InvalidArgumentException if amount is negative
     */
    public static function xymTransfer(
        SymbolFacade $facade,
        PublicKey $signer,
        string $recipient,
        float $amount,
        string $message = ''
    ): self {
        // Validate positive amount
        if ($amount < 0) {
            throw new \InvalidArgumentException(
                sprintf('XYM amount cannot be negative. Got: %f', $amount)
            );
        }

        return (new self($facade))
            ->signer($signer)
            ->to($recipient)
            ->amount($amount)
            ->message($message)
            ->autoFee();
    }

    // === Utility Methods ===

    public function getSigner(): ?PublicKey
    {
        return $this->signerPublicKey;
    }

    public function getRecipient(): ?Address
    {
        return $this->recipientAddress;
    }

    public function getMosaics(): array
    {
        return $this->mosaics;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getFeeMultiplier(): int
    {
        return $this->feeMultiplier;
    }

    public function isAutoFeeEnabled(): bool
    {
        return $this->autoCalculateFee;
    }
}