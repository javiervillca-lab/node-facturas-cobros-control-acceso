<?php

namespace App\control_acceso\domain;

interface SituacionFinancieraPortInterface
{
    public function consultar(int $pagadorId, \DateTimeImmutable $ahora): SituacionPago;
}
