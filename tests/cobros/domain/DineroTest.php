<?php

namespace App\Tests\cobros\domain;

use App\cobros\domain\Dinero;
use PHPUnit\Framework\TestCase;

final class DineroTest extends TestCase
{
    public function testParsesAndFormatsExactDecimalAmounts(): void
    {
        self::assertSame(12345, Dinero::aCentavos('123.45'));
        self::assertSame('123.45', Dinero::desdeCentavos(12345));
    }

    public function testMultipliesAndRoundsToMinorUnit(): void
    {
        self::assertSame('0.02', Dinero::multiplicar('0.33', '0.05'));
    }

    public function testAddsAmountsWithoutFloatingPointArithmetic(): void
    {
        self::assertSame('100.00', Dinero::sumar('99.99', '0.01'));
    }

    public function testRejectsExcessPrecision(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Dinero::aCentavos('1.001');
    }

    public function testRejectsNegativeAmounts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Dinero::aCentavos('-1.00');
    }
}
