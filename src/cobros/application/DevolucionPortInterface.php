<?php

namespace App\cobros\application;

use App\cobros\domain\DevolucionExceso;
use App\cobros\domain\ResultadoDevolucion;

interface DevolucionPortInterface
{
    public function ejecutar(DevolucionExceso $devolucion): ResultadoDevolucion;
}
