<?php

namespace App\cobros\infrastructure;

use App\cobros\application\DevolucionPortInterface;
use App\cobros\domain\DevolucionExceso;
use App\cobros\domain\ResultadoDevolucion;

final class SimulatedRefundAdapter implements DevolucionPortInterface
{
    public function __construct(private string $environment)
    {
    }

    public function ejecutar(DevolucionExceso $devolucion): ResultadoDevolucion
    {
        if ('prod' === $this->environment) {
            throw new \RuntimeException('la devolución simulada está deshabilitada en producción; configure un adaptador real');
        }
        if (null === $devolucion->getId()) {
            throw new \LogicException('la devolución debe guardarse antes de ejecutarse');
        }
        if ($devolucion->getEstado() !== DevolucionExceso::APROBADA) {
            throw new \DomainException('solo se puede simular una devolución aprobada');
        }

        return new ResultadoDevolucion(sprintf('SIM-DEV-%d', $devolucion->getId()), true);
    }
}
