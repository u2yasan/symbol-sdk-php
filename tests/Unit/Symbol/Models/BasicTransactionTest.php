<?php

declare(strict_types=1);

namespace SymbolSdk\Tests\Unit\Symbol\Models;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class BasicTransactionTest extends TestCase
{
    #[Test]
    public function can_load_autoloader(): void
    {
        self::assertTrue(class_exists('SymbolSdk\Symbol\Enums\NetworkType'));
        self::assertTrue(class_exists('SymbolSdk\Symbol\ValueObjects\Amount'));
    }

    #[Test]
    public function can_create_network_type(): void
    {
        $network = \SymbolSdk\Symbol\Enums\NetworkType::TESTNET;
        self::assertEquals(0x98, $network->value);
    }
}
