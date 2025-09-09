<?php

declare(strict_types=1);

namespace SymbolSdk\Tests\Unit\Symbol\Enums;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SymbolSdk\Symbol\Enums\NetworkType;

final class NetworkTypeTest extends TestCase
{
    #[Test]
    public function has_correct_values(): void
    {
        self::assertEquals(0x68, NetworkType::MAINNET->value);
        self::assertEquals(0x98, NetworkType::TESTNET->value);
    }

    #[Test]
    public function returns_correct_names(): void
    {
        self::assertEquals('mainnet', NetworkType::MAINNET->getName());
        self::assertEquals('testnet', NetworkType::TESTNET->getName());
    }

    #[Test]
    public function returns_recommended_nodes(): void
    {
        $mainnetNodes = NetworkType::MAINNET->getRecommendedNodes();
        $testnetNodes = NetworkType::TESTNET->getRecommendedNodes();

        self::assertIsArray($mainnetNodes);
        self::assertIsArray($testnetNodes);
        self::assertNotEmpty($mainnetNodes);
        self::assertNotEmpty($testnetNodes);

        // すべてのノードがHTTPS URLであることを確認
        foreach ($mainnetNodes as $node) {
            self::assertStringStartsWith('https://', $node);
            self::assertStringEndsWith(':3001', $node);
        }
    }

    #[Test]
    public function returns_all_available_nodes(): void
    {
        $allMainnetNodes = NetworkType::MAINNET->getAllAvailableNodes();
        $recommendedNodes = NetworkType::MAINNET->getRecommendedNodes();
        $fallbackNodes = NetworkType::MAINNET->getFallbackNodes();

        self::assertGreaterThanOrEqual(
            \count($recommendedNodes),
            \count($allMainnetNodes)
        );

        // 推奨ノードがすべて含まれていることを確認
        foreach ($recommendedNodes as $node) {
            self::assertContains($node, $allMainnetNodes);
        }
    }

    #[Test]
    public function creates_from_name(): void
    {
        self::assertEquals(NetworkType::MAINNET, NetworkType::fromName('mainnet'));
        self::assertEquals(NetworkType::TESTNET, NetworkType::fromName('testnet'));
        self::assertEquals(NetworkType::MAINNET, NetworkType::fromName('MAINNET'));
    }

    #[Test]
    public function throws_exception_for_invalid_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NetworkType::fromName('invalid');
    }
}
