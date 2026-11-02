<?php

namespace App\facturacion\infrastructure;

use App\facturacion\application\EnvioFacturaPortInterface;
use App\facturacion\domain\Factura;
use App\facturacion\domain\ResultadoEnvioFactura;

final class SimulatedInvoiceDeliveryAdapter implements EnvioFacturaPortInterface
{
    public function __construct(private string $environment)
    {
    }

    public function enviar(Factura $factura): ResultadoEnvioFactura
    {
        if ('prod' === $this->environment) {
            throw new \RuntimeException('el envío simulado está deshabilitado en producción; configure un canal real');
        }
        if (null === $factura->getId()) {
            throw new \LogicException('la factura debe guardarse antes de solicitar su envío');
        }

        return new ResultadoEnvioFactura(sprintf('SIM-ENVIO-%d', $factura->getId()), true);
    }
}
