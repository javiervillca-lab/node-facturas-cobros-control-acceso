<?php

namespace App\facturacion\application;

use App\facturacion\domain\Factura;
use App\facturacion\domain\FacturaRepositoryInterface;

class EnviarFacturaService
{
    public function __construct(private FacturaRepositoryInterface $facturas)
    {
    }

    public function ejecutar(int $facturaId): Factura
    {
        $factura = $this->facturas->obtener($facturaId);
        if (null === $factura) {
            throw new \DomainException('la factura no existe');
        }

        $factura->confirmarEnvio(new \DateTimeImmutable());
        $this->facturas->guardar($factura);

        return $factura;
    }
}
