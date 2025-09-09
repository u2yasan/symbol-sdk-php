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
        
        // 修正: 正しい引数名を使用
        $workflow = $this->facade->buildTransferWorkflow(
            $alice,              // sender パラメータ (位置引数)
            $bobAddress,         // recipientAddress
            15.75,              // xymAmount
            'Workflow test',    // message
            150                 // feeMultiplier
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
        
        // 正の金額を使用してエラーを回避
        $transaction = TransactionBuilder::xymTransfer(
            facade: $this->facade,
            signer: $alice->publicKey,
            recipient: $bob->address->toString(),
            amount: 25.5, // 正の値
            message: 'Builder test'
        )->build();
        
        self::assertEquals($alice->publicKey, $transaction->signerPublicKey);
        self::assertEquals($bob->address, $transaction->recipientAddress);
        self::assertEquals('Builder test', $transaction->message);
        self::assertGreaterThan(0, $transaction->fee->toInt());
    }

    // === エラーケースのテスト ===

    #[Test]
    public function negative_amount_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // 修正: 実際のエラーメッセージに合わせる
        $this->expectExceptionMessage('XYM amount cannot be negative');
        
        // 負の金額でAmountを作成しようとする
        $this->facade->createAmount(-10.5, true);
    }

    #[Test]
    public function negative_xym_transfer_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('XYM amount cannot be negative');
        
        $alice = $this->facade->createRandomAccount();
        $bob = $this->facade->createRandomAccount();
        
        // TransactionBuilderで負の金額を使用
        TransactionBuilder::xymTransfer(
            facade: $this->facade,
            signer: $alice->publicKey,
            recipient: $bob->address->toString(),
            amount: -5.0, // 負の値
            message: 'This should fail'
        );
    }

    #[Test]
    public function zero_amount_is_allowed(): void
    {
        // 0の金額は許可されるべき
        $amount = $this->facade->createAmount(0, false);
        self::assertEquals(0, $amount->getValue());
        self::assertTrue($amount->isZero());
    }

    #[Test]
    public function workflow_with_zero_amount(): void
    {
        $alice = $this->facade->createRandomAccount();
        $bobAddress = $this->facade->createRandomAccount()->address->toString();
        
        // 0 XYMでのワークフロー（メッセージのみのトランザクション）
        // 修正: 位置引数を使用
        $workflow = $this->facade->buildTransferWorkflow(
            $alice,                      // sender
            $bobAddress,                 // recipientAddress  
            0.0,                        // xymAmount
            'Message-only transaction'   // message
        );
        
        self::assertIsArray($workflow);
        self::assertEquals('0.00 XYM', $workflow['summary']['amount']);
        self::assertEquals('Message-only transaction', $workflow['summary']['message']);
    }

    #[Test]
    public function large_amount_is_handled(): void
    {
        // 大きな金額でのテスト
        $largeAmount = 1000.123456; // 1000.123456 XYM
        $amount = $this->facade->createAmount($largeAmount, true);
        
        self::assertEquals($largeAmount, $amount->toXym());
        self::assertEquals(1000123456, $amount->getValue()); // micro-XYM
    }

    #[Test]
    public function workflow_throws_exception_for_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');
        
        $alice = $this->facade->createRandomAccount();
        $bobAddress = $this->facade->createRandomAccount()->address->toString();
        
        // buildTransferWorkflowで負の金額を使用
        $this->facade->buildTransferWorkflow(
            $alice,
            $bobAddress,
            -5.0, // 負の値
            'This should fail'
        );
    }
}