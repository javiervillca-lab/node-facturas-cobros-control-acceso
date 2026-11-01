<?php

namespace App\control_acceso\infrastructure;

use App\control_acceso\application\RegistroHuellaPortInterface;
use App\control_acceso\domain\EvidenciaRegistroHuella;
use App\control_acceso\domain\OperacionRegistroHuella;

final class SimulatedFingerprintAdapter implements RegistroHuellaPortInterface
{
    public function __construct(private string $environment)
    {
    }

    public function ejecutar(OperacionRegistroHuella $operacion): EvidenciaRegistroHuella
    {
        if ('prod' === $this->environment) {
            throw new \RuntimeException('la operación de huella simulada está deshabilitada en producción; configure un adaptador real');
        }
        if (null === $operacion->getId()) {
            throw new \LogicException('la operación debe guardarse antes de ejecutarla');
        }
        if ($operacion->getEstado() !== OperacionRegistroHuella::SOLICITADA) {
            throw new \DomainException('solo se puede simular una operación de huella solicitada');
        }

        return new EvidenciaRegistroHuella(sprintf('SIM-HUELLA-%d', $operacion->getId()), true);
    }
}
