<?php

declare(strict_types=1);

namespace SymbolSdk\Facade;

use SymbolSdk\Symbol\Enums\NetworkType;
use SymbolSdk\Symbol\ValueObjects\MosaicId;
use SymbolSdk\Utils\NodeHealthChecker;

readonly class NetworkConfig
{
    public array $nodes;
    public string $generationHashSeed;
    public MosaicId $currencyMosaicId;
    public int $epochAdjustment;

    public function __construct(
        public NetworkType $networkType,
        ?array $customNodes = null
    ) {
        $this->nodes = $customNodes ?? $this->getDefaultNodes();
        $this->generationHashSeed = $networkType->getGenerationHashSeed();
        $this->currencyMosaicId = $this->getCurrencyMosaicId();
        $this->epochAdjustment = $this->getEpochAdjustment();
    }

    public static function testnet(?array $customNodes = null): self
    {
        return new self(NetworkType::TESTNET, $customNodes);
    }

    public static function mainnet(?array $customNodes = null): self
    {
        return new self(NetworkType::MAINNET, $customNodes);
    }

    private function getDefaultNodes(): array
    {
        return $this->networkType->getRecommendedNodes();
    }

    private function getCurrencyMosaicId(): MosaicId
    {
        return match($this->networkType) {
            NetworkType::MAINNET => new MosaicId('0x6BED913FA20223F8'),
            NetworkType::TESTNET => new MosaicId('0x72C0212E67A08BCE'),
        };
    }

    private function getEpochAdjustment(): int
    {
        // Symbol epoch adjustment in seconds
        return 1615853185;
    }

    public function getHealthyNodes(): array
    {
        $checker = new NodeHealthChecker();
        return $checker->getAliveNodesForNetwork($this->networkType);
    }

    public function getBestNode(): string
    {
        $checker = new NodeHealthChecker();
        $aliveNodes = $this->getHealthyNodes();
        
        if (empty($aliveNodes)) {
            return $this->nodes[0] ?? throw new \RuntimeException('No nodes configured');
        }

        $fastest = $checker->getFastestNode($aliveNodes);
        return $fastest ?? $aliveNodes[0];
    }

    public function getNodeUrl(string $path = ''): string
    {
        $baseUrl = rtrim($this->getBestNode(), '/');
        return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
    }
}
