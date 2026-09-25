<?php

namespace Tests\Unit;

use App\Support\EmailNormalizer;
use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    #[DataProvider('parsing')]
    public function test_parses_decimal_strings_without_floats(string $input, int $cents): void
    {
        $this->assertSame($cents, Money::of($input)->cents);
    }

    public static function parsing(): array
    {
        return [
            ['0.50', 50], ['12', 1200], ['.5', 50], ['1,250.99', 125099], ['-3.25', -325],
            ['0.005', 1], ['0.004', 0], ['$4.10', 410], ['0.1', 10],
        ];
    }

    public function test_rejects_garbage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of('abc');
    }

    public function test_arithmetic_is_exact(): void
    {
        // 0.1 + 0.2 is the classic float failure
        $this->assertSame('0.30', Money::of('0.10')->add(Money::of('0.20'))->toDecimal());
        $this->assertSame('-0.05', Money::of('0.10')->subtract(Money::of('0.15'))->toDecimal());
        $this->assertSame('6.50', Money::of('0.65')->multiply(10)->toDecimal());
    }

    public function test_percentage_rounds_half_up_to_the_cent(): void
    {
        $this->assertSame('0.15', Money::of('0.50')->percentage('30')->toDecimal());
        $this->assertSame('0.11', Money::of('0.35')->percentage('30')->toDecimal()); // 0.105 → 0.11
        $this->assertSame('0.04', Money::of('0.30')->percentage('12.5')->toDecimal()); // 0.0375 → 0.04
        $this->assertSame('0.00', Money::of('0.50')->percentage('0')->toDecimal());
    }

    public function test_formatting_and_rate(): void
    {
        $this->assertSame('$1,250.00', Money::of('1250')->format());
        $this->assertSame('-$0.50', Money::of('-0.5')->format());
        $this->assertSame('+$0.50', Money::of('0.5')->format(true));
        $this->assertSame('$0.100/min', Money::of('0.50')->perMinute(5));
        $this->assertSame('$0.117/min', Money::of('0.35')->perMinute(3));
    }

    public function test_email_normalization(): void
    {
        $this->assertSame('janedoe@gmail.com', EmailNormalizer::normalize('Jane.Doe+work@GoogleMail.com'));
        $this->assertSame('jane.doe@example.com', EmailNormalizer::normalize('jane.doe+x@example.com'));
    }
}
