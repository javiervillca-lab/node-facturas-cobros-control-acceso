<?php

namespace App\facturacion\domain;

use App\cobros\domain\CuentaPorCobrar;

interface FacturaRepositoryInterface
{
    public function obtener(int $id): ?Factura;

    public function obtenerPorCuenta(CuentaPorCobrar $cuenta): ?Factura;

    public function guardar(Factura $factura): void;

    /**
     * @return Factura[]
     */
    public function todas(): array;
}
