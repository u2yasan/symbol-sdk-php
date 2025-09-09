<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SymbolSdk\Facade\SymbolFacade;
use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\Builder\TransactionBuilder;

echo "=== Symbol SDK PHP 8.3+ Usage Examples ===" . PHP_EOL . PHP_EOL;

// 1. Initialize Facade
echo "1. Initializing Symbol Facade..." . PHP_EOL;
$facade = new SymbolFacade(NetworkType::TESTNET);

$networkInfo = $facade->getNetworkInfo();
echo "   Network: {$networkInfo['networkType']}" . PHP_EOL;
echo "   Currency: {$networkInfo['currencyMosaicId']}" . PHP_EOL;
echo "   Nodes: " . count($networkInfo['configuredNodes']) . " configured" . PHP_EOL . PHP_EOL;

// 2. Create accounts
echo "2. Creating accounts..." . PHP_EOL;
$alice = $facade->createRandomAccount();
$bob = $facade->createRandomAccount();

echo "   Alice: {$alice->address->toString()}" . PHP_EOL;
echo "   Bob: {$bob->address->toString()}" . PHP_EOL . PHP_EOL;

// 3. Create transfer using Facade
echo "3. Creating transfer transaction..." . PHP_EOL;
$transfer = $facade->createXymTransfer(
    signerPublicKey: $alice->publicKey,
    recipientAddress: $bob->address,
    amount: $facade->createAmount(10.5),
    message: 'Hello, Symbol!'
);

echo "   Amount: {$transfer->getTotalValue()->toXym()} XYM" . PHP_EOL;
echo "   Message: {$transfer->message}" . PHP_EOL . PHP_EOL;

// 4. Set fee and sign
echo "4. Setting fee and signing..." . PHP_EOL;
$transferWithFee = $facade->setMaxFee($transfer, 100);
$signedTransfer = $facade->signTransaction($transferWithFee, $alice);

echo "   Fee: {$signedTransfer->fee->toXym()} XYM" . PHP_EOL;
echo "   Size: {$signedTransfer->getSize()} bytes" . PHP_EOL;
echo "   Signed: " . ($signedTransfer->isSigned() ? '✅ YES' : '❌ NO') . PHP_EOL . PHP_EOL;

// 5. Alternative: Using Builder Pattern
echo "5. Alternative: Using Transaction Builder..." . PHP_EOL;
$builtTransaction = TransactionBuilder::xymTransfer(
    facade: $facade,
    signer: $alice->publicKey,
    recipient: $bob->address->toString(),
    amount: 25.75,
    message: 'Built with Builder pattern'
)->build();

$signedBuilt = $facade->signTransaction($builtTransaction, $alice);
echo "   Built amount: {$signedBuilt->getTotalValue()->toXym()} XYM" . PHP_EOL;
echo "   Built message: {$signedBuilt->message}" . PHP_EOL . PHP_EOL;

// 6. Complete workflow
echo "6. Complete transfer workflow..." . PHP_EOL;
$workflow = $facade->buildTransferWorkflow(
    sender: $alice,
    recipientAddress: $bob->address->toString(),
    xymAmount: 50.0,
    message: 'Complete workflow example'
);

$summary = $workflow['summary'];
echo "   From: {$summary['from']}" . PHP_EOL;
echo "   To: {$summary['to']}" . PHP_EOL;
echo "   Amount: {$summary['amount']}" . PHP_EOL;
echo "   Fee: {$summary['fee']}" . PHP_EOL;
echo "   Hash: {$summary['hash']}" . PHP_EOL . PHP_EOL;

// 7. Account from mnemonic
echo "7. Creating account from mnemonic..." . PHP_EOL;
$mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
$charlie = $facade->createAccountFromMnemonic($mnemonic);
echo "   Charlie: {$charlie->address->toString()}" . PHP_EOL;
echo "   (Deterministic from mnemonic)" . PHP_EOL . PHP_EOL;

// 8. Node health check
echo "8. Checking node health..." . PHP_EOL;
$healthyNodes = $facade->getHealthyNodes();
echo "   Healthy nodes: " . count($healthyNodes) . "/" . count($facade->config->nodes) . PHP_EOL;
if (!empty($healthyNodes)) {
    echo "   Best node: " . $facade->getBestNode() . PHP_EOL;
}

echo PHP_EOL . "✅ All examples completed successfully!" . PHP_EOL;
