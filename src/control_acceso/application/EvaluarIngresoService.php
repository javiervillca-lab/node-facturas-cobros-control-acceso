<?php

namespace App\control_acceso\application;

use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\control_acceso\domain\EvaluadorDeIngreso;
use App\control_acceso\domain\SituacionFinancieraPortInterface;

class EvaluarIngresoService
{
    public function __construct(
        private AccesoMiembroRepositoryInterface $accesos,
        private SituacionFinancieraPortInterface $situacionFinanciera,
        private DerechoAccesoPortInterface $derechos,
    ) {
    }

    public function evaluar(int $accesoId): array
    {
        $acceso = $this->accesos->obtener($accesoId);
        if (null === $acceso) {
            throw new \DomainException('el acceso no existe');
        }

        $ahora = new \DateTimeImmutable();
        $miembroId = $acceso->getMiembroId();
        $situacion = $this->situacionFinanciera->consultar($miembroId, $ahora);
        $tieneDeuda = $situacion->tieneObligacionBloqueante($ahora);
        $derecho = $this->derechos->consultar($miembroId, $ahora);

        return EvaluadorDeIngreso::evaluarIngreso($acceso, $tieneDeuda, $derecho, $ahora);
    }
}
