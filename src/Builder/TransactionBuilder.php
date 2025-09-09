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
    private Amount $fee;

    public function __construct(
        private readonly SymbolFacade $facade
    ) {
        $this->fee = Amount::zero();
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
        };
        return $this;
    }

    public function amount(Amount|float|int $amount): self
    {
        $amountObj = match(true) {
            $amount instanceof Amount => $amount,
            is_numeric($amount) => Amount::fromXym((float) $amount),
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
        };
        return $this;
    }

    public function fee(Amount|int $fee): self
    {
        $this->fee = match(true) {
            $fee instanceof Amount => $fee,
            is_int($fee) => new Amount($fee),
        };
        return $this;
    }

    public function autoFee(int $feeMultiplier = 100): self
    {
        // Fee will be calculated after transaction is built
        $this->fee = new Amount(-$feeMultiplier); // Negative indicates auto-calculation
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

        // Handle auto fee calculation
        if ($this->fee->toInt() < 0) {
            $feeMultiplier = abs($this->fee->toInt());
            return $this->facade->setMaxFee($transaction, $feeMultiplier);
        }

        return $transaction->withFee($this->fee);
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

    public static function xymTransfer(
        SymbolFacade $facade,
        PublicKey $signer,
        string $recipient,
        float $amount,
        string $message = ''
    ): self {
        return (new self($facade))
            ->signer($signer)
            ->to($recipient)
            ->amount($amount)
            ->message($message)
            ->autoFee();
    }
}
