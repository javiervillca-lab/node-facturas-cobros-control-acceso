<?php

namespace App\Tests\control_acceso\domain;

use App\control_acceso\domain\DerechoAcceso;
use PHPUnit\Framework\TestCase;

final class DerechoAccesoTest extends TestCase
{
    public function testNormalScheduleAllowsWeekdayBeforeClosing(): void
    {
        $derecho = new DerechoAcceso(true, null, 'normal', true);
        $ahora = new \DateTimeImmutable('2026-11-02 17:59:00', new \DateTimeZone('America/La_Paz'));

        self::assertTrue($derecho->permiteIngreso($ahora));
    }

    public function testNormalScheduleExcludesClosingTimeAndWeekends(): void
    {
        $derecho = new DerechoAcceso(true, null, 'normal', true);

        self::assertFalse($derecho->permiteIngreso(new \DateTimeImmutable('2026-11-02 18:00:00', new \DateTimeZone('America/La_Paz'))));
        self::assertFalse($derecho->permiteIngreso(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('America/La_Paz'))));
    }

    public function testExpiredRightDoesNotAllowEntry(): void
    {
        $derecho = new DerechoAcceso(true, new \DateTimeImmutable('2026-11-02 10:00:00'), 'continuo', true);

        self::assertFalse($derecho->permiteIngreso(new \DateTimeImmutable('2026-11-02 10:00:00')));
    }
}
