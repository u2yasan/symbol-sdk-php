<?php

declare(strict_types=1);

namespace SymbolSdk\Tests\Unit\Symbol\Enums;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function returns_correct_generation_hash_seed(): void
    {
        $mainnetSeed = NetworkType::MAINNET->getGenerationHashSeed();
        $testnetSeed = NetworkType::TESTNET->getGenerationHashSeed();

        self::assertEquals(64, strlen($mainnetSeed));
        self::assertEquals(64, strlen($testnetSeed));
        self::assertNotEquals($mainnetSeed, $testnetSeed);
    }

    #[Test]
    #[DataProvider('provide_network_names')]
    public function creates_from_name(string $name, NetworkType $expected): void
    {
        $result = NetworkType::fromName($name);
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function throws_exception_for_invalid_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NetworkType::fromName('invalid');
    }

    public static function provide_network_names(): \Generator
    {
        yield 'mainnet lowercase' => ['mainnet', NetworkType::MAINNET];
        yield 'mainnet uppercase' => ['MAINNET', NetworkType::MAINNET];
        yield 'testnet lowercase' => ['testnet', NetworkType::TESTNET];
        yield 'testnet uppercase' => ['TESTNET', NetworkType::TESTNET];
    }

    #[Test]
    public function returns_default_nodes(): void
    {
        $mainnetNodes = NetworkType::MAINNET->getDefaultNodes();
        $testnetNodes = NetworkType::TESTNET->getDefaultNodes();

        self::assertIsArray($mainnetNodes);
        self::assertIsArray($testnetNodes);
        self::assertNotEmpty($mainnetNodes);
        self::assertNotEmpty($testnetNodes);

        // Check that all nodes are valid URLs
        foreach ($mainnetNodes as $node) {
            self::assertIsString($node);
            self::assertTrue(filter_var($node, FILTER_VALIDATE_URL) !== false);
        }

        foreach ($testnetNodes as $node) {
            self::assertIsString($node);
            self::assertTrue(filter_var($node, FILTER_VALIDATE_URL) !== false);
        }
    }
}
