<?php

namespace App\control_acceso\application;

use App\control_acceso\domain\DerechoAcceso;

interface DerechoAccesoPortInterface
{
    public function consultar(int $miembroId, \DateTimeImmutable $ahora): DerechoAcceso;
}
