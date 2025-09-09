<?php

declare(strict_types=1);

namespace SymbolSdk\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use SymbolSdk\Facade\SymbolFacade;
use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Builder\TransactionBuilder;

#[Group('integration')]
final class SymbolFacadeTest extends TestCase
{
    private SymbolFacade $facade;

    protected function setUp(): void
    {
        $this->facade = new SymbolFacade(NetworkType::TESTNET);
    }

    #[Test]
    public function creates_facade_from_network_type(): void
    {
        $facade = new SymbolFacade(NetworkType::TESTNET);
        
        self::assertEquals(NetworkType::TESTNET, $facade->config->networkType);
        self::assertIsArray($facade->config->nodes);
        self::assertNotEmpty($facade->config->nodes);
    }

    #[Test]
    public function creates_facade_from_string(): void
    {
        $facade = new SymbolFacade('testnet');
        
        self::assertEquals(NetworkType::TESTNET, $facade->config->networkType);
    }

    #[Test]
    public function creates_random_account(): void
    {
        $account = $this->facade->createRandomAccount();
        
        self::assertEquals(NetworkType::TESTNET, $account->networkType);
        self::assertEquals(64, strlen($account->privateKey->toString()));
        self::assertEquals(64, strlen($account->publicKey->toString()));
        self::assertEquals(39, strlen($account->address->toString()));
    }

    #[Test]
    public function creates_account_from_mnemonic(): void
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $account = $this->facade->createAccountFromMnemonic($mnemonic);
        
        // Should be deterministic
        self::assertEquals(NetworkType::TESTNET, $account->networkType);
        
        $account2 = $this->facade->createAccountFromMnemonic($mnemonic);
        self::assertTrue($account->privateKey->equals($account2->privateKey));
    }

    #[Test]
    public function creates_xym_transfer_transaction(): void
    {
        $alice = $this->facade->createRandomAccount();
        $bob = $this->facade->createRandomAccount();
        
        $transfer = $this->facade->createXymTransfer(
            signerPublicKey: $alice->publicKey,
            recipientAddress: $bob->address,
            amount: $this->facade->createAmount(10.5),
            message: 'Integration test transfer'
        );
        
        self::assertEquals($alice->publicKey, $transfer->signerPublicKey);
        self::assertEquals($bob->address, $transfer->recipientAddress);
        self::assertEquals('Integration test transfer', $transfer->message);
        self::assertCount(1, $transfer->mosaics);
    }

    #[Test]
    public function signs_and_announces_transaction(): void
    {
        $alice = $this->facade->createRandomAccount();
        $bob = $this->facade->createRandomAccount();
        
        $transfer = $this->facade->createXymTransfer(
            signerPublicKey: $alice->publicKey,
            recipientAddress: $bob->address,
            amount: $this->facade->createAmount(5.0),
            message: 'Test payment'
        );
        
        $transferWithFee = $this->facade->setMaxFee($transfer);
        $signedTransfer = $this->facade->signTransaction($transferWithFee, $alice);
        
        self::assertTrue($signedTransfer->isSigned());
        
        $announcementData = $this->facade->announceTransaction($signedTransfer);
        
        self::assertIsArray($announcementData);
        self::assertArrayHasKey('payload', $announcementData);
        self::assertArrayHasKey('hash', $announcementData);
        self::assertEquals('TRANSFER', $announcementData['type']);
        self::assertEquals('testnet', $announcementData['network']);
    }

    #[Test]
    public function builds_complete_transfer_workflow(): void
    {
        $alice = $this->facade->createRandomAccount();
        $bobAddress = $this->facade->createRandomAccount()->address->toString();
        
        $workflow = $this->facade->buildTransferWorkflow(
            sender: $alice,
            recipientAddress: $bobAddress,
            xymAmount: 15.75,
            message: 'Workflow test',
            feeMultiplier: 150
        );
        
        self::assertIsArray($workflow);
        self::assertArrayHasKey('transaction', $workflow);
        self::assertArrayHasKey('announcementData', $workflow);
        self::assertArrayHasKey('summary', $workflow);
        
        $summary = $workflow['summary'];
        self::assertEquals($alice->address->toString(), $summary['from']);
        self::assertEquals($bobAddress, $summary['to']);
        self::assertEquals('15.75 XYM', $summary['amount']);
        self::assertEquals('Workflow test', $summary['message']);
    }

    #[Test]
    public function validates_addresses(): void
    {
        $validAddress = $this->facade->createRandomAccount()->address->toString();
        $invalidAddress = 'INVALID_ADDRESS';
        
        self::assertTrue($this->facade->validateAddress($validAddress));
        self::assertFalse($this->facade->validateAddress($invalidAddress));
    }

    #[Test]
    public function gets_network_info(): void
    {
        $info = $this->facade->getNetworkInfo();
        
        self::assertIsArray($info);
        self::assertEquals('testnet', $info['networkType']);
        self::assertEquals(NetworkType::TESTNET->value, $info['networkValue']);
        self::assertArrayHasKey('generationHashSeed', $info);
        self::assertArrayHasKey('currencyMosaicId', $info);
        self::assertArrayHasKey('configuredNodes', $info);
    }

    #[Test]
    public function transaction_builder_works(): void
    {
        $alice = $this->facade->createRandomAccount();
        $bob = $this->facade->createRandomAccount();
        
        $transaction = TransactionBuilder::xymTransfer(
            facade: $this->facade,
            signer: $alice->publicKey,
            recipient: $bob->address->toString(),
            amount: 25.5,
            message: 'Builder test'
        )->build();
        
        self::assertEquals($alice->publicKey, $transaction->signerPublicKey);
        self::assertEquals($bob->address, $transaction->recipientAddress);
        self::assertEquals('Builder test', $transaction->message);
        self::assertGreaterThan(0, $transaction->fee->toInt());
    }
}
