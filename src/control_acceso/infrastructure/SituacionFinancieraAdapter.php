<?php

namespace App\control_acceso\infrastructure;

use App\cobros\infrastructure\SituacionFinancieraQuery;
use App\control_acceso\domain\SituacionFinancieraPortInterface;
use App\Entity\Cliente;

/**
 * ACL del lado de Control de Acceso para traducir una consulta financiera al
 * valor que este contexto necesita, sin compartir entidades de dominio.
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
