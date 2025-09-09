<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\Enums;

enum MosaicFlags: int
{
    case NONE = 0x00;
    case SUPPLY_MUTABLE = 0x01;
    case TRANSFERABLE = 0x02;
    case RESTRICTABLE = 0x04;
    case REVOKABLE = 0x08;

    public function hasFlag(self $flag): bool
    {
        return ($this->value & $flag->value) !== 0;
    }

    public static function combine(self ...$flags): int
    {
        $combined = 0;
        foreach ($flags as $flag) {
            $combined |= $flag->value;
        }
        return $combined;
    }

    public static function fromValue(int $value): array
    {
        $flags = [];

        if (($value & self::SUPPLY_MUTABLE->value) !== 0) {
            $flags[] = self::SUPPLY_MUTABLE;
        }
        if (($value & self::TRANSFERABLE->value) !== 0) {
            $flags[] = self::TRANSFERABLE;
        }
        if (($value & self::RESTRICTABLE->value) !== 0) {
            $flags[] = self::RESTRICTABLE;
        }
        if (($value & self::REVOKABLE->value) !== 0) {
            $flags[] = self::REVOKABLE;
        }

        return empty($flags) ? [self::NONE] : $flags;
    }

    public function getName(): string
    {
        return match($this) {
            self::NONE => 'NONE',
            self::SUPPLY_MUTABLE => 'SUPPLY_MUTABLE',
            self::TRANSFERABLE => 'TRANSFERABLE',
            self::RESTRICTABLE => 'RESTRICTABLE',
            self::REVOKABLE => 'REVOKABLE',
        };
    }
}
