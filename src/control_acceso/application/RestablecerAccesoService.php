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
        private RegistroHuellaPortInterface $registroHuella,
        private DerechoAccesoPortInterface $derechos,
    ) {
    }

    public function evaluarYSolicitar(int $accesoId, string $autorizadoPor): array
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

        $evaluacion = EvaluadorDeIngreso::evaluarRestablecimiento($tieneDeuda || !$derecho->vigente);

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

        $operacion = $acceso->buscarOperacion($operacionId);
        if (null === $operacion) {
            throw new \DomainException('la operación no existe en este acceso');
        }
        if ($operacion->getTipo() !== \App\control_acceso\domain\OperacionRegistroHuella::ALTA
            || $operacion->getEstado() !== \App\control_acceso\domain\OperacionRegistroHuella::SOLICITADA) {
            throw new \DomainException('la operación no está disponible para confirmar un restablecimiento');
        }

        $evidencia = $this->registroHuella->ejecutar($operacion);
        $acceso->confirmarRestablecimiento($operacionId, $evidencia);
        $this->accesos->guardar($acceso);
    }
}
