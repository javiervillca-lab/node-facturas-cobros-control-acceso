<?php

namespace App\facturacion\application;

use App\cobros\domain\CuentaPorCobrarRepositoryInterface;
use App\facturacion\domain\Factura;
use App\facturacion\domain\FacturaRepositoryInterface;

class EmitirFacturaService
{
    public function __construct(
        private CuentaPorCobrarRepositoryInterface $cuentas,
        private FacturaRepositoryInterface $facturas,
    ) {
    }

    public function preparar(int $cuentaId): Factura
    {
        $cuenta = $this->cuentas->obtener($cuentaId);
        if (null === $cuenta) {
            throw new \DomainException('la cuenta no existe');
        }

        $existente = $this->facturas->obtenerPorCuenta($cuenta);
        if (null !== $existente) {
            return $existente;
        }

        $factura = Factura::prepararDesde($cuenta);
        $this->facturas->guardar($factura);

        return $factura;
    }

    public function confirmarEmision(int $facturaId): Factura
    {
        $factura = $this->facturas->obtener($facturaId);
        if (null === $factura) {
            throw new \DomainException('la factura no existe');
        }

        $referencia = sprintf('SIN-%s-%d', date('Ymd'), $factura->getId());
        $factura->confirmarEmision($referencia, new \DateTimeImmutable());
        $this->facturas->guardar($factura);

        return $factura;
    }
}
