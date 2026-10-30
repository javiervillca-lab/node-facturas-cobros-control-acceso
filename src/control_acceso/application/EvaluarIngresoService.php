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
    ) {
    }

    public function evaluar(int $accesoId): array
    {
        $acceso = $this->accesos->obtener($accesoId);
        if (null === $acceso) {
            throw new \DomainException('el acceso no existe');
        }

        $tieneDeuda = $this->situacionFinanciera->tieneDeudaVencidaBloqueante($acceso->getMiembro(), new \DateTimeImmutable());

        return EvaluadorDeIngreso::evaluarIngreso($acceso, $tieneDeuda);
    }
}
