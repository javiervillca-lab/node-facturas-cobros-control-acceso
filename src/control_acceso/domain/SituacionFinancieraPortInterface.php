<?php

namespace App\control_acceso\domain;

use App\Entity\Cliente;

interface SituacionFinancieraPortInterface
{
    public function tieneDeudaVencidaBloqueante(Cliente $cliente, \DateTimeImmutable $ahora): bool;
}
