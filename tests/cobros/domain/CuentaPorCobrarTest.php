<?php

namespace App\Tests\cobros\domain;

use App\Entity\Cliente;
use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\VerificacionPago;
use PHPUnit\Framework\TestCase;

final class CuentaPorCobrarTest extends TestCase
{
    public function testOnlyVerifiedPaymentsReduceBalanceAndExcessIsSeparate(): void
    {
        $cuenta = $this->crearCuenta();

        $aplicacion = $cuenta->registrarPagoVerificado(
            new VerificacionPago('VER-1', 'TX-1', 'simulador', '120.00', 'BOB', 1, true)
        );

        self::assertSame('100.00', $aplicacion->getImporteAplicado());
        self::assertSame('20.00', $aplicacion->getExceso());
        self::assertSame('0.00', $cuenta->getSaldo());
        self::assertSame(CuentaPorCobrar::SALDADA, $cuenta->getEstado());
        self::assertCount(1, $cuenta->getDevoluciones());
    }

    public function testRepeatedReferenceWithSameAmountIsIdempotent(): void
    {
        $cuenta = $this->crearCuenta();
        $verificacion = new VerificacionPago('VER-1', 'TX-1', 'simulador', '40.00', 'BOB', 1, true);

        $primera = $cuenta->registrarPagoVerificado($verificacion);
        $segunda = $cuenta->registrarPagoVerificado(
            new VerificacionPago('VER-2', 'TX-1', 'simulador', '40.00', 'BOB', 1, true)
        );

        self::assertSame($primera, $segunda);
        self::assertSame('60.00', $cuenta->getSaldo());
        self::assertCount(1, $cuenta->getAplicaciones());
    }

    public function testRepeatedReferenceWithDifferentAmountIsRejected(): void
    {
        $cuenta = $this->crearCuenta();
        $cuenta->registrarPagoVerificado(
            new VerificacionPago('VER-1', 'TX-1', 'simulador', '40.00', 'BOB', 1, true)
        );

        $this->expectException(\DomainException::class);
        $cuenta->registrarPagoVerificado(
            new VerificacionPago('VER-2', 'TX-1', 'simulador', '41.00', 'BOB', 1, true)
        );
    }

    private function crearCuenta(): CuentaPorCobrar
    {
        $cliente = new Cliente('Prueba', 'test@example.invalid', 'mensual');
        $id = new \ReflectionProperty(Cliente::class, 'id');
        $id->setValue($cliente, 1);

        return CuentaPorCobrar::crear($cliente, '2026-11', 'BOB', [[
            'tipo' => 'cuota',
            'descripcion' => 'Cuota mensual',
            'cantidad' => '1',
            'precio' => '100.00',
        ]]);
    }
}
