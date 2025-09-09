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

    /**
     * 2025年現在の推奨ノード（レスポンス確認済み）
     */
    public function getRecommendedNodes(): array
    {
        return match($this) {
            self::MAINNET => [
                // 日本のノード（レスポンス速度が良好）
                'https://sym-main-01.opening-line.jp:3001',
                'https://sym-main-02.opening-line.jp:3001',
                'https://sym-main-03.opening-line.jp:3001',
                // 欧州のノード
                'https://symbol.harvest-monitor.com:3001',
                'https://symbol-mikun.net:3001',
                // その他の安定ノード
                'https://symbol.census.moe:3001',
            ],
            self::TESTNET => [
                // 公式テストネットノード
                'https://sym-test-01.opening-line.jp:3001',
                'https://sym-test-02.opening-line.jp:3001',
                'https://sym-test-03.opening-line.jp:3001',
                // NGL推奨テストネットノード
                'https://test.symbol.census.moe:3001',
            ],
        };
    }

    /**
     * フォールバック用ノード（バックアップ）
     */
    public function getFallbackNodes(): array
    {
        return match($this) {
            self::MAINNET => [
                'https://symbol-node.net:3001',
                'https://00.symbol-node.net:3001',
                'https://01.symbol-node.net:3001',
            ],
            self::TESTNET => [
                'https://test-symbol-node.net:3001',
            ],
        };
    }

    /**
     * すべての利用可能なノード（推奨 + フォールバック）
     */
    public function getAllAvailableNodes(): array
    {
        return array_merge(
            $this->getRecommendedNodes(),
            $this->getFallbackNodes()
        );
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
