<?php

namespace App\control_acceso\application;

use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\control_acceso\domain\EvaluadorDeIngreso;
use App\control_acceso\domain\SituacionFinancieraPortInterface;

class RestablecerAccesoService
{
    public function __construct(
        private AccesoMiembroRepositoryInterface $accesos,
        private SituacionFinancieraPortInterface $situacionFinanciera,
    ) {
    }

    public function evaluarYSolicitar(int $accesoId, string $autorizadoPor): array
    {
        $acceso = $this->accesos->obtener($accesoId);
        if (null === $acceso) {
            throw new \DomainException('el acceso no existe');
        }

        $tieneDeuda = $this->situacionFinanciera->tieneDeudaVencidaBloqueante($acceso->getMiembro(), new \DateTimeImmutable());
        $evaluacion = EvaluadorDeIngreso::evaluarRestablecimiento($tieneDeuda);

        if ($evaluacion !== EvaluadorDeIngreso::FAVORABLE) {
            return ['solicitada' => false, 'motivo' => 'las obligaciones exigibles todavia no estan regularizadas'];
        }

        $operacion = $acceso->solicitarRestablecimiento($autorizadoPor);
        $this->accesos->guardar($acceso);

        return ['solicitada' => true, 'operacionId' => $operacion->getId()];
    }

    public function confirmar(int $accesoId, int $operacionId): void
    {
        $acceso = $this->accesos->obtener($accesoId);
        if (null === $acceso) {
            throw new \DomainException('el acceso no existe');
        }

        $acceso->confirmarRestablecimiento($operacionId);
        $this->accesos->guardar($acceso);
    }
}
