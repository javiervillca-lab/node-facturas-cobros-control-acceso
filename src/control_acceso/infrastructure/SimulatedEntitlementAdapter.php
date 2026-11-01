<?php

namespace App\control_acceso\infrastructure;

use App\control_acceso\application\DerechoAccesoPortInterface;
use App\control_acceso\domain\DerechoAcceso;

final class SimulatedEntitlementAdapter implements DerechoAccesoPortInterface
{
    public function __construct(private string $environment)
    {
    }

    public function consultar(int $miembroId, string $planId, \DateTimeImmutable $ahora): DerechoAcceso
    {
        if ('prod' === $this->environment) {
            throw new \RuntimeException('los derechos de acceso simulados están deshabilitados en producción; configure un proveedor de derechos');
        }

        if ($miembroId <= 0) {
            throw new \LogicException('el derecho requiere un miembro persistido');
        }
        $plan = strtolower(trim($planId));
        $horario = in_array($plan, ['continuo', '24/7'], true) ? 'continuo' : 'normal';

        return new DerechoAcceso('' !== $plan, null, $horario, true);
    }
}
