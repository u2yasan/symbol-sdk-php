<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\ValueObjects;

use InvalidArgumentException;

final readonly class Amount
{
    private int $value;

    public function __construct(int|string $value)
    {
        $this->value = $this->validateAndNormalize($value);
    }

    /**
     * Create Amount from XYM value
     * 
     * @param float $xym Amount in XYM
     * @return static
     * @throws InvalidArgumentException if XYM amount is negative
     */
    public static function fromXym(float $xym): static
    {
        if ($xym < 0) {
            throw new InvalidArgumentException(
                sprintf('XYM amount cannot be negative. Got: %f', $xym)
            );
        }

        // Convert to micro-XYM (6 decimal places)
        $microXym = (int) round($xym * 1_000_000);
        return new static($microXym);
    }

    /**
     * Create Amount from micro-XYM value
     * 
     * @param int $microXym Amount in micro-XYM
     * @return static
     * @throws InvalidArgumentException if micro-XYM amount is negative
     */
    public static function fromMicroXym(int $microXym): static
    {
        return new static($microXym);
    }

    /**
     * Create zero amount
     * 
     * @return static
     */
    public static function zero(): static
    {
        return new static(0);
    }

    /**
     * Create amount from string representation
     * 
     * @param string $value
     * @return static
     * @throws InvalidArgumentException if value is invalid
     */
    public static function fromString(string $value): static
    {
        return new static($value);
    }

    private function validateAndNormalize(int|string $value): int
    {
        // Convert string to integer
        if (is_string($value)) {
            if (!is_numeric($value)) {
                throw new InvalidArgumentException(
                    sprintf('Amount must be numeric. Got: "%s"', $value)
                );
            }
            $value = (int) $value;
        }

        // Check for negative values
        if ($value < 0) {
            throw new InvalidArgumentException(
                sprintf('Amount cannot be negative. Got: %d micro-XYM', $value)
            );
        }

        // Check maximum value (Symbol max supply: 8,999,999,999 XYM = 8,999,999,999,000,000 micro-XYM)
        $maxMicroXym = 8_999_999_999_000_000;
        if ($value > $maxMicroXym) {
            throw new InvalidArgumentException(
                sprintf(
                    'Amount exceeds maximum allowed value of %d micro-XYM (%.6f XYM). Got: %d micro-XYM',
                    $maxMicroXym,
                    $maxMicroXym / 1_000_000,
                    $value
                )
            );
        }

        return $value;
    }

    /**
     * Get the raw micro-XYM value
     * 
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Get the raw micro-XYM value (alias for getValue)
     * 
     * @return int
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Get string representation of micro-XYM value
     * 
     * @return string
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Magic method for string conversion
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Convert to XYM (floating point)
     * 
     * @return float
     */
    public function toXym(): float
    {
        return $this->value / 1_000_000;
    }

    /**
     * Convert to formatted XYM string
     * 
     * @param int $decimals Number of decimal places (default: 6)
     * @return string
     */
    public function toXymString(int $decimals = 6): string
    {
        return number_format($this->toXym(), $decimals);
    }

    /**
     * Convert to formatted XYM string with unit
     * 
     * @param int $decimals Number of decimal places (default: 6)
     * @return string
     */
    public function toFormattedString(int $decimals = 6): string
    {
        return $this->toXymString($decimals) . ' XYM';
    }

    // === Arithmetic Operations ===

    /**
     * Add amount
     * 
     * @param Amount $other
     * @return Amount
     * @throws InvalidArgumentException if result would be negative or exceed maximum
     */
    public function add(Amount $other): Amount
    {
        return new Amount($this->value + $other->value);
    }

    /**
     * Subtract amount
     * 
     * @param Amount $other
     * @return Amount
     * @throws InvalidArgumentException if result would be negative
     */
    public function subtract(Amount $other): Amount
    {
        $result = $this->value - $other->value;
        return new Amount($result); // Will throw if negative
    }

    /**
     * Multiply by scalar
     * 
     * @param int|float $multiplier Must be non-negative
     * @return Amount
     * @throws InvalidArgumentException if multiplier is negative
     */
    public function multiply(int|float $multiplier): Amount
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException(
                sprintf('Multiplier cannot be negative. Got: %s', $multiplier)
            );
        }

        $result = (int) round($this->value * $multiplier);
        return new Amount($result);
    }

    /**
     * Divide by scalar
     * 
     * @param int|float $divisor Must be positive
     * @return Amount
     * @throws InvalidArgumentException if divisor is non-positive
     */
    public function divide(int|float $divisor): Amount
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException(
                sprintf('Divisor must be positive. Got: %s', $divisor)
            );
        }

        $result = (int) round($this->value / $divisor);
        return new Amount($result);
    }

    // === Comparison Methods ===

    /**
     * Check if amount is zero
     * 
     * @return bool
     */
    public function isZero(): bool
    {
        return $this->value === 0;
    }

    /**
     * Check if amount is positive
     * 
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * Compare with another amount
     * 
     * @param Amount $other
     * @return int -1 if less, 0 if equal, 1 if greater
     */
    public function compare(Amount $other): int
    {
        return $this->value <=> $other->value;
    }

    /**
     * Check if equals to another amount
     * 
     * @param Amount $other
     * @return bool
     */
    public function equals(Amount $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Check if greater than another amount
     * 
     * @param Amount $other
     * @return bool
     */
    public function greaterThan(Amount $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Check if greater than or equal to another amount
     * 
     * @param Amount $other
     * @return bool
     */
    public function greaterThanOrEqual(Amount $other): bool
    {
        return $this->value >= $other->value;
    }

    /**
     * Check if less than another amount
     * 
     * @param Amount $other
     * @return bool
     */
    public function lessThan(Amount $other): bool
    {
        return $this->value < $other->value;
    }

    /**
     * Check if less than or equal to another amount
     * 
     * @param Amount $other
     * @return bool
     */
    public function lessThanOrEqual(Amount $other): bool
    {
        return $this->value <= $other->value;
    }

    // === Utility Methods ===

    /**
     * Get the minimum of two amounts
     * 
     * @param Amount $other
     * @return Amount
     */
    public function min(Amount $other): Amount
    {
        return $this->lessThan($other) ? $this : $other;
    }

    /**
     * Get the maximum of two amounts
     * 
     * @param Amount $other
     * @return Amount
     */
    public function max(Amount $other): Amount
    {
        return $this->greaterThan($other) ? $this : $other;
    }

    /**
     * Create array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'microXym' => $this->value,
            'xym' => $this->toXym(),
            'formatted' => $this->toFormattedString(),
        ];
    }

    /**
     * Create JSON representation
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}