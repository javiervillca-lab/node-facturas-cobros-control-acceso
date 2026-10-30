<?php

namespace App\control_acceso\infrastructure;

use App\cobros\infrastructure\SituacionFinancieraQuery;
use App\control_acceso\domain\SituacionFinancieraPortInterface;
use App\Entity\Cliente;

/**
 * Anti-Corruption Layer: traduce la consulta publica de Cobros al unico
 * valor que Control de Acceso necesita, sin tocar sus entidades internas.
 */
class SituacionFinancieraAdapter implements SituacionFinancieraPortInterface
{
    public function __construct(private SituacionFinancieraQuery $query)
    {
    }

    public function tieneDeudaVencidaBloqueante(Cliente $cliente, \DateTimeImmutable $ahora): bool
    {
        return $this->query->tieneDeudaVencidaBloqueante($cliente, $ahora);
    }
}
