<?php

namespace App\control_acceso\infrastructure;

use App\control_acceso\application\NotificacionSuspensionPortInterface;
use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\ResultadoAviso;

final class SimulatedSuspensionNoticeAdapter implements NotificacionSuspensionPortInterface
{
    public function __construct(private string $environment)
    {
    }

    public function enviar(AccesoMiembro $acceso, array $obligacionIds, \DateTimeImmutable $ahora): ResultadoAviso
    {
        if ('prod' === $this->environment) {
            throw new \RuntimeException('el aviso simulado está deshabilitado en producción; configure un canal con constancia de entrega');
        }
        if (null === $acceso->getId() || [] === $obligacionIds) {
            throw new \LogicException('el aviso requiere un acceso persistido y obligaciones identificadas');
        }

        sort($obligacionIds);
        $ids = implode('-', $obligacionIds);

        return new ResultadoAviso(
            sprintf('SIM-AVISO-%d-%s', $acceso->getId(), substr(hash('sha256', $ids), 0, 24)),
            $ahora,
            true,
        );
    }
}
