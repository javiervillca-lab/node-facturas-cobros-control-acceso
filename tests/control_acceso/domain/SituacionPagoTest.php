<?php

namespace App\Tests\control_acceso\domain;

use App\control_acceso\domain\SituacionPago;
use PHPUnit\Framework\TestCase;

final class SituacionPagoTest extends TestCase
{
    public function testDebtBecomesBlockingAfterFiveCalendarDays(): void
    {
        $vence = new \DateTimeImmutable('2026-10-27 09:00:00', new \DateTimeZone('America/La_Paz'));
        $situacion = new SituacionPago(42, [['id' => 7, 'vencimiento' => $vence]], $vence);

        self::assertFalse($situacion->tieneObligacionBloqueante(new \DateTimeImmutable('2026-11-01 08:59:59', new \DateTimeZone('America/La_Paz'))));
        self::assertTrue($situacion->tieneObligacionBloqueante(new \DateTimeImmutable('2026-11-01 09:00:00', new \DateTimeZone('America/La_Paz'))));
    }

    public function testObligationIdsAreSortedAndUnique(): void
    {
        $ahora = new \DateTimeImmutable('2026-11-01 09:00:00', new \DateTimeZone('America/La_Paz'));
        $situacion = new SituacionPago(42, [
            ['id' => 12, 'vencimiento' => new \DateTimeImmutable('2026-10-26 09:00:00', new \DateTimeZone('America/La_Paz'))],
            ['id' => 3, 'vencimiento' => new \DateTimeImmutable('2026-10-27 09:00:00', new \DateTimeZone('America/La_Paz'))],
            ['id' => 12, 'vencimiento' => new \DateTimeImmutable('2026-10-26 09:00:00', new \DateTimeZone('America/La_Paz'))],
        ], $ahora);

        self::assertSame([3, 12], $situacion->idsObligacionesBloqueantes($ahora));
    }

    public function testNoticeIncludesOnlyObligationsAtLeastFiveDaysOverdue(): void
    {
        $ahora = new \DateTimeImmutable('2026-11-01 09:00:00', new \DateTimeZone('America/La_Paz'));
        $situacion = new SituacionPago(42, [
            ['id' => 3, 'vencimiento' => new \DateTimeImmutable('2026-10-27 09:00:00', new \DateTimeZone('America/La_Paz'))],
            ['id' => 12, 'vencimiento' => new \DateTimeImmutable('2026-10-30 09:00:00', new \DateTimeZone('America/La_Paz'))],
        ], $ahora);

        self::assertSame([3], $situacion->idsObligacionesBloqueantes($ahora));
    }
}
