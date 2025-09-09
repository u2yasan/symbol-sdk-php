<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Enums;

enum TransactionType: int
{
    case TRANSFER = 0x4154;
    case MOSAIC_DEFINITION = 0x414D;
    case MOSAIC_SUPPLY_CHANGE = 0x424D;
    case AGGREGATE_COMPLETE = 0x4141;
    case AGGREGATE_BONDED = 0x4241;
    case ACCOUNT_KEY_LINK = 0x414C;
    case VRF_KEY_LINK = 0x4243;
    case VOTING_KEY_LINK = 0x4143;
    case NODE_KEY_LINK = 0x424C;

    public function getName(): string
    {
        return match($this) {
            self::TRANSFER => 'TRANSFER',
            self::MOSAIC_DEFINITION => 'MOSAIC_DEFINITION',
            self::MOSAIC_SUPPLY_CHANGE => 'MOSAIC_SUPPLY_CHANGE',
            self::AGGREGATE_COMPLETE => 'AGGREGATE_COMPLETE',
            self::AGGREGATE_BONDED => 'AGGREGATE_BONDED',
            self::ACCOUNT_KEY_LINK => 'ACCOUNT_KEY_LINK',
            self::VRF_KEY_LINK => 'VRF_KEY_LINK',
            self::VOTING_KEY_LINK => 'VOTING_KEY_LINK',
            self::NODE_KEY_LINK => 'NODE_KEY_LINK',
        };
    }

    public function isAggregateTransaction(): bool
    {
        return match($this) {
            self::AGGREGATE_COMPLETE, self::AGGREGATE_BONDED => true,
            default => false,
        };
    }

    public function requiresSignature(): bool
    {
        return true; // すべてのトランザクションは署名が必要
    }

    public static function fromName(string $name): self
    {
        return match(strtoupper($name)) {
            'TRANSFER' => self::TRANSFER,
            'MOSAIC_DEFINITION' => self::MOSAIC_DEFINITION,
            'MOSAIC_SUPPLY_CHANGE' => self::MOSAIC_SUPPLY_CHANGE,
            'AGGREGATE_COMPLETE' => self::AGGREGATE_COMPLETE,
            'AGGREGATE_BONDED' => self::AGGREGATE_BONDED,
            'ACCOUNT_KEY_LINK' => self::ACCOUNT_KEY_LINK,
            'VRF_KEY_LINK' => self::VRF_KEY_LINK,
            'VOTING_KEY_LINK' => self::VOTING_KEY_LINK,
            'NODE_KEY_LINK' => self::NODE_KEY_LINK,
            default => throw new \InvalidArgumentException("Unknown transaction type: $name"),
        };
    }
}
