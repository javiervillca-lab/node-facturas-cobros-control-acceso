<?php

namespace App\control_acceso\application;

use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\ResultadoAviso;

interface NotificacionSuspensionPortInterface
{
    /**
     * @param list<int> $obligacionIds
     */
    public function enviar(AccesoMiembro $acceso, array $obligacionIds, \DateTimeImmutable $ahora): ResultadoAviso;
}
