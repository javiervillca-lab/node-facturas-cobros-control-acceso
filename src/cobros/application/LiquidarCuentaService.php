<?php

namespace App\cobros\application;

use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\CuentaPorCobrarRepositoryInterface;
use App\Entity\Cliente;

class LiquidarCuentaService
{
    public function __construct(private CuentaPorCobrarRepositoryInterface $cuentas)
    {
    }

    /**
     * @param array<int, array{tipo: string, descripcion: string, cantidad: string|int, precio: string|int, descuento?: string|int}> $conceptosData
     */
    public function ejecutar(Cliente $cliente, array $conceptosData, ?string $periodo, ?\DateTimeImmutable $vencimiento): CuentaPorCobrar
    {
        $cuenta = CuentaPorCobrar::crear($cliente, $periodo, 'BOB', $conceptosData, $vencimiento);

        $this->cuentas->guardar($cuenta);

        return $cuenta;
    }
}
