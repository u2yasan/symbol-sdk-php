<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\ValueObjects;

readonly class Amount
{
    public \GMP $value;

    public function __construct(int|string|\GMP $amount)
    {
        $this->value = match(true) {
            \is_int($amount) => gmp_init($amount),
            \is_string($amount) => gmp_init($amount, 10),
            $amount instanceof \GMP => $amount,
            default => throw new \InvalidArgumentException('Invalid amount type'),
        };

        if (gmp_cmp($this->value, 0) < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        // Symbol max supply check (8,999,999,999 XYM = 8,999,999,999,000,000 micro-XYM)
        $maxSupply = gmp_init('8999999999000000');
        if (gmp_cmp($this->value, $maxSupply) > 0) {
            throw new \InvalidArgumentException('Amount exceeds maximum supply');
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromMicroXym(int|string $microXym): self
    {
        return new self($microXym);
    }

    public static function fromXym(float $xym): self
    {
        if ($xym < 0) {
            throw new \InvalidArgumentException('XYM amount cannot be negative');
        }

        $microXym = (int) round($xym * 1_000_000);
        return new self($microXym);
    }

    public function add(self $other): self
    {
        return new self(gmp_add($this->value, $other->value));
    }

    public function subtract(self $other): self
    {
        $result = gmp_sub($this->value, $other->value);
        if (gmp_cmp($result, 0) < 0) {
            throw new \InvalidArgumentException('Subtraction would result in negative amount');
        }
        return new self($result);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new \InvalidArgumentException('Multiplier cannot be negative');
        }
        return new self(gmp_mul($this->value, $multiplier));
    }

    public function divide(int $divisor): self
    {
        if ($divisor <= 0) {
            throw new \InvalidArgumentException('Divisor must be positive');
        }
        return new self(gmp_div($this->value, $divisor));
    }

    public function toString(): string
    {
        return gmp_strval($this->value);
    }

    public function toInt(): int
    {
        return gmp_intval($this->value);
    }

    public function toXym(): float
    {
        return (float) gmp_strval($this->value) / 1_000_000;
    }

    public function equals(self $other): bool
    {
        return gmp_cmp($this->value, $other->value) === 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return gmp_cmp($this->value, $other->value) > 0;
    }

    public function isLessThan(self $other): bool
    {
        return gmp_cmp($this->value, $other->value) < 0;
    }

    public function isZero(): bool
    {
        return gmp_cmp($this->value, 0) === 0;
    }
}
