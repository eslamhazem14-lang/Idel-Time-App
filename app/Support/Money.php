<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable, decimal-safe money value stored as integer minor units (cents).
 *
 * Floats are never used: values are parsed from and serialized to decimal
 * strings, and all arithmetic is integer arithmetic.
 */
final class Money implements JsonSerializable, Stringable
{
    private function __construct(public readonly int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Parse a decimal string such as "12", "0.5", "-3.25" or "1,250.00".
     * Values with more than two decimals are rounded half-up.
     */
    public static function of(Money|string|int $value): self
    {
        if ($value instanceof self) {
            return $value;
        }
        if (is_int($value)) {
            return new self($value * 100);
        }

        $value = str_replace([',', ' ', '$'], '', trim($value));
        if (stripos($value, 'e') !== false && is_numeric($value)) {
            $value = sprintf('%.6F', (float) $value); // aggregate results from some drivers
        }
        if (! preg_match('/^(-)?(\d*)(?:\.(\d*))?$/', $value, $m) || ($m[2] === '' && ($m[3] ?? '') === '')) {
            throw new InvalidArgumentException("Invalid money amount [{$value}].");
        }

        $negative = $m[1] === '-';
        $whole = (int) ($m[2] === '' ? '0' : $m[2]);
        $fraction = str_pad($m[3] ?? '', 3, '0');
        $cents = $whole * 100 + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            $cents++;
        }

        return new self($negative ? -$cents : $cents);
    }

    /** Sum a DECIMAL column from a query builder without floating point. */
    public static function sum($query, string $column): self
    {
        return self::of((string) ($query->sum($column) ?: '0'));
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(Money $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    public function negate(): self
    {
        return new self(-$this->cents);
    }

    public function abs(): self
    {
        return new self(abs($this->cents));
    }

    /**
     * Percentage of this amount, rounded half-up to the cent.
     * The percentage is a decimal string with up to 2 decimals, e.g. "30" or "12.5".
     */
    public function percentage(string $percent): self
    {
        $basisPoints = self::of($percent)->cents; // "30.00" => 3000 bp
        $product = $this->cents * $basisPoints;
        $sign = $product < 0 ? -1 : 1;

        return new self($sign * intdiv(abs($product) + 5000, 10000));
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function greaterThan(Money $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function lessThan(Money $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents;
    }

    public static function max(Money $a, Money $b): self
    {
        return $a->cents >= $b->cents ? $a : $b;
    }

    /** Plain decimal string for storage and APIs, e.g. "-12.05". */
    public function toDecimal(): string
    {
        $abs = abs($this->cents);

        return ($this->cents < 0 ? '-' : '').intdiv($abs, 100).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Human format, e.g. "$1,250.00" or "-$0.50". */
    public function format(bool $signed = false): string
    {
        $abs = abs($this->cents);
        $symbol = config('platform.currency_symbol', '$');
        $formatted = $symbol.number_format(intdiv($abs, 100)).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        if ($this->cents < 0) {
            return '-'.$formatted;
        }

        return ($signed && $this->cents > 0 ? '+' : '').$formatted;
    }

    /** Reward per minute, formatted with three decimals ("$0.100/min"). Integer math only. */
    public function perMinute(int $minutes): string
    {
        $minutes = max(1, $minutes);
        $millis = intdiv($this->cents * 10 + intdiv($minutes, 2), $minutes); // tenths of a cent
        $symbol = config('platform.currency_symbol', '$');

        return $symbol.intdiv($millis, 1000).'.'.str_pad((string) ($millis % 1000), 3, '0', STR_PAD_LEFT).'/min';
    }

    public function jsonSerialize(): string
    {
        return $this->toDecimal();
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }
}
