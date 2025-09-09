<?php

declare(strict_types=1);

namespace SymbolSdk\Tests\Unit\Symbol\Models;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use SymbolSdk\Symbol\Models\{TransferTransaction, UnresolvedMosaic};
use SymbolSdk\Symbol\Enums\{NetworkType, TransactionType};
use SymbolSdk\Symbol\ValueObjects\{Amount, Timestamp, Address, MosaicId};
use SymbolSdk\CryptoTypes\PublicKey;

final class TransferTransactionTest extends TestCase
{
    private const SAMPLE_PUBLIC_KEY = '4C4BD7F8E1E1AC61DB817089F9416A7EDC18339F06CDC851495B271533FAD13B';
    private const SAMPLE_ADDRESS = 'TBEM3LTBAHSDOXONNOKAVIGIZJLUCCPIBWY7YEA';

    private function createValidTimestamp(): Timestamp
    {
        // Symbol epoch: 2021-03-16 00:06:25 UTC (1615852585)
        // Use a timestamp well after Symbol epoch
        return new Timestamp(time() + 7200); // 2 hours from now
    }

    #[Test]
    public function creates_transfer_transaction(): void
    {
        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS),
            message: 'Hello Symbol!'
        );

        self::assertEquals(TransactionType::TRANSFER, $transaction->type);
        self::assertEquals(NetworkType::TESTNET, $transaction->network);
        self::assertEquals('Hello Symbol!', $transaction->message);
        self::assertCount(0, $transaction->mosaics);
    }

    #[Test]
    public function creates_transaction_with_mosaics(): void
    {
        $mosaic = new UnresolvedMosaic(
            new MosaicId('0x72C0212E67A08BCE'),
            new Amount(1000000)
        );

        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS),
            mosaics: [$mosaic],
            message: 'Transfer with XYM'
        );

        self::assertCount(1, $transaction->mosaics);
        self::assertEquals('Transfer with XYM', $transaction->message);
    }

    #[Test]
    public function serializes_correctly(): void
    {
        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS),
            message: 'test'
        );

        $serialized = $transaction->serialize();

        self::assertIsString($serialized);
        self::assertTrue(ctype_xdigit($serialized));
        self::assertGreaterThan(200, strlen($serialized)); // Should have reasonable size
    }

    #[Test]
    public function calculates_size_correctly(): void
    {
        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS),
            message: 'test'
        );

        $size = $transaction->getSize();
        $serialized = $transaction->serialize();

        // Size should match serialized length (in bytes, so divide hex length by 2)
        self::assertEquals($size, strlen($serialized) / 2);
    }

    #[Test]
    public function adds_mosaics_correctly(): void
    {
        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS)
        );

        $mosaic = new UnresolvedMosaic(
            new MosaicId('0x72C0212E67A08BCE'),
            new Amount(1000000)
        );

        $newTransaction = $transaction->addMosaic($mosaic);

        self::assertCount(0, $transaction->mosaics);
        self::assertCount(1, $newTransaction->mosaics);
        self::assertNotSame($transaction, $newTransaction);
    }

    #[Test]
    public function calculates_total_value(): void
    {
        $mosaic1 = new UnresolvedMosaic(new MosaicId('0x1111'), new Amount(100));
        $mosaic2 = new UnresolvedMosaic(new MosaicId('0x2222'), new Amount(200));

        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS),
            mosaics: [$mosaic1, $mosaic2]
        );

        $totalValue = $transaction->getTotalValue();
        self::assertEquals('300', $totalValue->toString());
    }

    #[Test]
    public function handles_empty_message(): void
    {
        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS),
            message: ''
        );

        self::assertEquals('', $transaction->message);
        
        $serialized = $transaction->serialize();
        self::assertIsString($serialized);
        self::assertTrue(ctype_xdigit($serialized));
    }

    #[Test]
    public function validates_transaction_info(): void
    {
        $transaction = new TransferTransaction(
            network: NetworkType::TESTNET,
            signerPublicKey: new PublicKey(self::SAMPLE_PUBLIC_KEY),
            deadline: $this->createValidTimestamp(),
            recipientAddress: new Address(self::SAMPLE_ADDRESS),
            message: 'test'
        );

        $info = $transaction->getTransactionInfo();

        self::assertEquals('TRANSFER', $info['type']);
        self::assertEquals('testnet', $info['network']);
        self::assertEquals(1, $info['version']);
        self::assertFalse($info['signed']);
        self::assertNull($info['hash']);
    }
}
