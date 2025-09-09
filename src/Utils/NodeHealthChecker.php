<?php

declare(strict_types=1);

namespace SymbolSdk\Utils;

use SymbolSdk\Symbol\Enums\NetworkType;

final readonly class NodeHealthChecker
{
    public function __construct(
        private int $timeoutSeconds = 5,
        private int $maxRetries = 3
    ) {
    }

    /**
     * ノードが生きているかチェック
     */
    public function isNodeAlive(string $nodeUrl): bool
    {
        $nodeInfoUrl = rtrim($nodeUrl, '/') . '/node/info';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => [
                    'Accept: application/json',
                    'User-Agent: SymbolSdk-PHP/1.0'
                ]
            ]
        ]);

        for ($i = 0; $i < $this->maxRetries; $i++) {
            $response = @file_get_contents($nodeInfoUrl, false, $context);
            if ($response !== false) {
                $data = json_decode($response, true);
                return \is_array($data) && isset($data['publicKey']);
            }

            if ($i < $this->maxRetries - 1) {
                usleep(500000); // 0.5秒待機
            }
        }

        return false;
    }

    /**
     * 生きているノードのみを返す
     */
    public function filterAliveNodes(array $nodes): array
    {
        $aliveNodes = [];

        foreach ($nodes as $node) {
            if ($this->isNodeAlive($node)) {
                $aliveNodes[] = $node;
            }
        }

        return $aliveNodes;
    }

    /**
     * ネットワークタイプから生きているノードを取得
     */
    public function getAliveNodesForNetwork(NetworkType $networkType): array
    {
        $allNodes = $networkType->getAllAvailableNodes();
        $aliveNodes = $this->filterAliveNodes($allNodes);

        // 生きているノードがない場合は、すべてのノードを返す（フォールバック）
        return empty($aliveNodes) ? $allNodes : $aliveNodes;
    }

    /**
     * 最も速いノードを取得
     */
    public function getFastestNode(array $nodes): ?string
    {
        $fastest = null;
        $fastestTime = PHP_FLOAT_MAX;

        foreach ($nodes as $node) {
            $startTime = microtime(true);
            if ($this->isNodeAlive($node)) {
                $responseTime = microtime(true) - $startTime;
                if ($responseTime < $fastestTime) {
                    $fastestTime = $responseTime;
                    $fastest = $node;
                }
            }
        }

        return $fastest;
    }
}
