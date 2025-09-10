<?php

declare(strict_types=1);

namespace SymbolSdk\Symbol\ValueObjects;

readonly class Timestamp
{
    public \GMP $value;

    public function __construct(int|\DateTimeInterface|\GMP|null $timestamp = null)
    {
        $this->value = match(true) {
            $timestamp === null => gmp_init(time() * 1000),
            is_int($timestamp) => gmp_init($timestamp * 1000), // Convert seconds to milliseconds
            $timestamp instanceof \GMP => $timestamp,
            $timestamp instanceof \DateTimeInterface => gmp_init($timestamp->getTimestamp() * 1000),
            default => throw new \InvalidArgumentException('Invalid timestamp type'),
        };

        // Symbol epoch start: 2021-03-16 00:06:25 UTC (1615852585)
        // In milliseconds: 1615852585000
        $symbolEpochStart = gmp_init(1615852585000);
        if (gmp_cmp($this->value, $symbolEpochStart) < 0) {
            throw new \InvalidArgumentException(
                'Timestamp cannot be before Symbol epoch start (2021-03-16 00:06:25 UTC). ' .
                'Given: ' . $this->toDateTime()->format('Y-m-d H:i:s') . ' UTC'
            );
        }
    }

    public static function now(): self
    {
        return new self();
    }

    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return new self($dateTime);
    }

    public static function fromUnixTimestamp(int $unixTimestamp): self
    {
        return new self($unixTimestamp);
    }

    public static function fromMilliseconds(int $milliseconds): self
    {
        return new self(gmp_init($milliseconds));
    }

    public function addSeconds(int $seconds): self
    {
        return new self(gmp_intval(gmp_div($this->value, 1000)) + $seconds);
    }

    public function addMinutes(int $minutes): self
    {
        return $this->addSeconds($minutes * 60);
    }

    public function addHours(int $hours): self
    {
        return $this->addMinutes($hours * 60);
    }

    public function addDays(int $days): self
    {
        return $this->addHours($days * 24);
    }

    public function toDateTime(): \DateTimeImmutable
    {
        $unixTimestamp = gmp_intval(gmp_div($this->value, 1000));
        return new \DateTimeImmutable('@' . $unixTimestamp);
    }

    public function toUnixTimestamp(): int
    {
        return gmp_intval(gmp_div($this->value, 1000));
    }

    public function toString(): string
    {
        return gmp_strval($this->value);
    }

    public function toInt(): int
    {
        return gmp_intval($this->value);
    }

    public function equals(self $other): bool
    {
        return gmp_cmp($this->value, $other->value) === 0;
    }

    public function isAfter(self $other): bool
    {
        return gmp_cmp($this->value, $other->value) > 0;
    }

    public function isBefore(self $other): bool
    {
        return gmp_cmp($this->value, $other->value) < 0;
    }

    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->toDateTime()->format($format);
    }
}
