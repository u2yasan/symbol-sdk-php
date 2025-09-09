<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Enums;

enum NetworkType: int
{
    case MAINNET = 0x68;
    case TESTNET = 0x98;

    public function getGenerationHashSeed(): string
    {
        return match($this) {
            self::MAINNET => '57F7DA205008026C776CB6AED843393F04CD458E0AA2D9F1D5F31A402072B2D6',
            self::TESTNET => '7FCCD304802016BEBBCD342A332F91FF1F3BB5E902988B352697BE245F48E836',
        };
    }

    public function getDefaultNodes(): array
    {
        return match($this) {
            self::MAINNET => [
                'https://symbol.harvesting.farm:3001',
                'https://dual-01.symbol.farm:3001',
            ],
            self::TESTNET => [
                'https://beacon-01.us-west-1.testnet.symboldev.network:3001',
                'https://beacon-01.us-east-1.testnet.symboldev.network:3001',
            ],
        };
    }

    public function getName(): string
    {
        return match($this) {
            self::MAINNET => 'mainnet',
            self::TESTNET => 'testnet',
        };
    }

    public static function fromName(string $name): self
    {
        return match(strtolower($name)) {
            'mainnet' => self::MAINNET,
            'testnet' => self::TESTNET,
            default => throw new \InvalidArgumentException("Unknown network name: $name"),
        };
    }
}
