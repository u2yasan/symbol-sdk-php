<?php

declare(strict_types=1);

namespace SymbolSdk\Utils;

use SymbolSdk\CryptoTypes\PublicKey;
use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\Symbol\Models\{TransferTransaction, UnresolvedMosaic};
use SymbolSdk\Symbol\ValueObjects\{Address, Amount, MosaicId, Timestamp};

final class TransactionFactory
{
    public static function createTransfer(
        NetworkType $network,
        PublicKey $signerPublicKey,
        Address $recipientAddress,
        array $mosaics = [],
        string $message = '',
        ?Timestamp $deadline = null
    ): TransferTransaction {
        $deadline ??= Timestamp::now()->addHours(2);

        return new TransferTransaction(
            network: $network,
            signerPublicKey: $signerPublicKey,
            deadline: $deadline,
            recipientAddress: $recipientAddress,
            mosaics: $mosaics,
            message: $message
        );
    }

    public static function createXymTransfer(
        NetworkType $network,
        PublicKey $signerPublicKey,
        Address $recipientAddress,
        Amount $amount,
        string $message = '',
        ?Timestamp $deadline = null
    ): TransferTransaction {
        $xymMosaicId = match($network) {
            NetworkType::MAINNET => new MosaicId('0x6BED913FA20223F8'),
            NetworkType::TESTNET => new MosaicId('0x72C0212E67A08BCE'),
        };

        $mosaic = new UnresolvedMosaic($xymMosaicId, $amount);

        return self::createTransfer(
            network: $network,
            signerPublicKey: $signerPublicKey,
            recipientAddress: $recipientAddress,
            mosaics: [$mosaic],
            message: $message,
            deadline: $deadline
        );
    }

    public static function calculateTransactionFee(int $transactionSize, int $feeMultiplier = 100): Amount
    {
        return new Amount($transactionSize * $feeMultiplier);
    }

    public static function setMaxFee(TransferTransaction $transaction, int $feeMultiplier = 100): TransferTransaction
    {
        $fee = self::calculateTransactionFee($transaction->getSize(), $feeMultiplier);
        return $transaction->withFee($fee);
    }
}
