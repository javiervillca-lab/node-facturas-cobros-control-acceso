<?php

namespace App\facturacion\infrastructure;

use App\facturacion\application\EmisionFiscalPortInterface;
use App\facturacion\domain\Factura;
use App\facturacion\domain\ResultadoEmisionFiscal;

final class SimulatedFiscalIssuanceAdapter implements EmisionFiscalPortInterface
{
    public function __construct(private string $environment)
    {
    }

    public function emitir(Factura $factura): ResultadoEmisionFiscal
    {
        if ('prod' === $this->environment) {
            throw new \RuntimeException('la emisión fiscal simulada está deshabilitada en producción; configure un adaptador real');
        }
        if (null === $factura->getId()) {
            throw new \LogicException('la factura debe guardarse antes de solicitar emisión');
        }

        return new ResultadoEmisionFiscal(
            sprintf('SIM-SIN-%d', $factura->getId()),
            $factura->getTotal(),
            $factura->getMoneda(),
            true,
        );
    }
}
