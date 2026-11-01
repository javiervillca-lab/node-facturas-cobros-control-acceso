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
        private EmisionFiscalPortInterface $emisionFiscal,
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
        if ($factura->getEstado() === Factura::EMITIDA || $factura->getEstado() === Factura::ENVIADA) {
            return $factura;
        }
        if ($factura->getEstado() !== Factura::PENDIENTE_DE_EMISION) {
            throw new \DomainException('la factura no está disponible para emisión');
        }

        $resultado = $this->emisionFiscal->emitir($factura);
        $factura->confirmarEmision($resultado, new \DateTimeImmutable());
        $this->facturas->guardar($factura);

        return $factura;
    }
}
