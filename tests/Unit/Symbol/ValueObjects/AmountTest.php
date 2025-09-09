<?php

declare(strict_types=1);

namespace SymbolSdk\Tests\Unit\Symbol\ValueObjects;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use SymbolSdk\Symbol\ValueObjects\Amount;

final class AmountTest extends TestCase
{
    #[Test]
    public function creates_from_integer(): void
    {
        $amount = new Amount(1000000);
        self::assertEquals('1000000', $amount->toString());
        self::assertEquals(1.0, $amount->toXym());
    }

    #[Test]
    public function creates_from_string(): void
    {
        $amount = new Amount('1500000');
        self::assertEquals('1500000', $amount->toString());
        self::assertEquals(1.5, $amount->toXym());
    }

    #[Test]
    public function creates_from_xym(): void
    {
        $amount = Amount::fromXym(2.5);
        self::assertEquals('2500000', $amount->toString());
        self::assertEquals(2.5, $amount->toXym());
    }

    #[Test]
    public function creates_zero_amount(): void
    {
        $zero = Amount::zero();
        self::assertTrue($zero->isZero());
        self::assertEquals('0', $zero->toString());
    }

    #[Test]
    public function adds_amounts(): void
    {
        $amount1 = new Amount(1000000);
        $amount2 = new Amount(500000);
        $result = $amount1->add($amount2);
        
        self::assertEquals('1500000', $result->toString());
        self::assertEquals(1.5, $result->toXym());
    }

    #[Test]
    public function subtracts_amounts(): void
    {
        $amount1 = new Amount(2000000);
        $amount2 = new Amount(500000);
        $result = $amount1->subtract($amount2);
        
        self::assertEquals('1500000', $result->toString());
    }

    #[Test]
    public function throws_on_negative_subtraction(): void
    {
        $amount1 = new Amount(500000);
        $amount2 = new Amount(1000000);
        
        $this->expectException(\InvalidArgumentException::class);
        $amount1->subtract($amount2);
    }

    #[Test]
    public function throws_on_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Amount(-100);
    }

    #[Test]
    public function throws_on_exceeding_max_supply(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Amount('9999999999000000'); // Exceeds max supply
    }

    #[Test]
    public function compares_amounts(): void
    {
        $amount1 = new Amount(1000000);
        $amount2 = new Amount(2000000);
        $amount3 = new Amount(1000000);

        self::assertTrue($amount2->isGreaterThan($amount1));
        self::assertTrue($amount1->isLessThan($amount2));
        self::assertTrue($amount1->equals($amount3));
        self::assertFalse($amount1->equals($amount2));
    }
}
