<?php

namespace App\facturacion\application;

use App\facturacion\domain\Factura;
use App\facturacion\domain\FacturaRepositoryInterface;

class EnviarFacturaService
{
    public function __construct(
        private FacturaRepositoryInterface $facturas,
        private EnvioFacturaPortInterface $envio,
    ) {
    }

    public function ejecutar(int $facturaId): Factura
    {
        $factura = $this->facturas->obtener($facturaId);
        if (null === $factura) {
            throw new \DomainException('la factura no existe');
        }
        if ($factura->getEstado() === Factura::ENVIADA) {
            return $factura;
        }
        if ($factura->getEstado() !== Factura::EMITIDA) {
            throw new \DomainException('solo una factura emitida puede enviarse');
        }

        $resultado = $this->envio->enviar($factura);
        $factura->confirmarEnvio($resultado, new \DateTimeImmutable());
        $this->facturas->guardar($factura);

        return $factura;
    }
}
