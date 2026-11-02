<?php

namespace App\control_acceso\application;

use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\control_acceso\domain\AvisoPrevio;
use App\control_acceso\domain\AvisoPrevioRepositoryInterface;
use App\control_acceso\domain\EvaluadorDeIngreso;
use App\control_acceso\domain\SituacionFinancieraPortInterface;

class SuspenderAccesoService
{
    public function __construct(
        private AccesoMiembroRepositoryInterface $accesos,
        private SituacionFinancieraPortInterface $situacionFinanciera,
        private RegistroHuellaPortInterface $registroHuella,
        private AvisoPrevioRepositoryInterface $avisos,
        private NotificacionSuspensionPortInterface $notificacion,
    ) {
    }

    public function evaluarYSolicitar(int $accesoId): array
    {
        $acceso = $this->accesos->obtener($accesoId);
        if (null === $acceso) {
            throw new \DomainException('el acceso no existe');
        }

        $ahora = new \DateTimeImmutable();
        $pagadorId = $acceso->getMiembroId();
        $situacion = $this->situacionFinanciera->consultar($pagadorId, $ahora);
        $tieneDeuda = $situacion->tieneObligacionBloqueante($ahora);
        $evaluacion = EvaluadorDeIngreso::evaluarSuspension($tieneDeuda);

        if ($evaluacion !== EvaluadorDeIngreso::FAVORABLE) {
            return ['solicitada' => false, 'motivo' => 'no hay obligaciones vencidas por más de cinco días que justifiquen la suspensión'];
        }

        $pagadorId = $situacion->pagadorId;
        $obligacionIds = $situacion->idsObligacionesBloqueantes($ahora);
        $aviso = $this->avisos->buscarMasReciente($acceso, $pagadorId, $obligacionIds);
        if (null === $aviso) {
            $resultado = $this->notificacion->enviar($acceso, $obligacionIds, $ahora);
            $aviso = new AvisoPrevio($acceso, $pagadorId, $obligacionIds, $resultado);
            $this->avisos->guardar($aviso);

            return [
                'solicitada' => false,
                'avisoEnviado' => true,
                'motivo' => sprintf('aviso previo enviado%s; la suspensión no puede solicitarse antes de %s', $resultado->simulado ? ' (SIMULADO)' : '', $resultado->enviadoEn->modify('+24 hours')->format('Y-m-d H:i:s')),
            ];
        }

        $momentoPermitido = $aviso->getEnviadoEn()->modify('+24 hours');
        if ($ahora < $momentoPermitido) {
            return [
                'solicitada' => false,
                'esperaAviso' => true,
                'motivo' => sprintf('debe cumplirse el aviso previo de 24 horas%s; disponible desde %s', $aviso->esSimulado() ? ' (SIMULADO)' : '', $momentoPermitido->format('Y-m-d H:i:s')),
            ];
        }

        $operacion = $acceso->solicitarSuspension('obligacion vencida hace mas de cinco dias');
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
        if ($operacion->getTipo() !== \App\control_acceso\domain\OperacionRegistroHuella::RETIRO_IMPAGO
            || $operacion->getEstado() !== \App\control_acceso\domain\OperacionRegistroHuella::SOLICITADA) {
            throw new \DomainException('la operación no está disponible para confirmar una suspensión');
        }

        $evidencia = $this->registroHuella->ejecutar($operacion);
        $acceso->confirmarSuspension($operacionId, $evidencia);
        $this->accesos->guardar($acceso);
    }
}
