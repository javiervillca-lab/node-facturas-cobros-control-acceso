<?php

namespace App\control_acceso\infrastructure;

use App\cobros\infrastructure\SituacionFinancieraQuery;
use App\control_acceso\domain\SituacionFinancieraPortInterface;
use App\control_acceso\domain\SituacionPago;

/**
 * ACL del lado de Control de Acceso para traducir una consulta financiera al
 * valor que este contexto necesita, sin compartir entidades de dominio.
 */
class SituacionFinancieraAdapter implements SituacionFinancieraPortInterface
{
    public function __construct(private SituacionFinancieraQuery $query)
    {
    }

    public function consultar(int $pagadorId, \DateTimeImmutable $ahora): SituacionPago
    {
        if ($pagadorId <= 0) {
            throw new \LogicException('el pagador debe estar persistido para consultar su situación financiera');
        }

        return new SituacionPago($pagadorId, $this->query->obligacionesVencidas($pagadorId, $ahora), $ahora);
    }
}
