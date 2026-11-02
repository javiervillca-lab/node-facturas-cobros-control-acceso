<?php

namespace App\cobros\infrastructure;

use App\cobros\domain\CuentaPorCobrar;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Servicio publico (Open Host Service) de Facturacion y Cobros: traduce el
 * estado interno de las cuentas en el unico dato que otro contexto necesita.
 */
class SituacionFinancieraQuery
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function obligacionesVencidas(int $pagadorId, \DateTimeImmutable $ahora): array
    {
        $cuentas = $this->em->createQueryBuilder()
            ->select('cuenta')
            ->from(CuentaPorCobrar::class, 'cuenta')
            ->where('IDENTITY(cuenta.cliente) = :pagadorId')
            ->andWhere('cuenta.estado = :estado')
            ->andWhere('cuenta.vencimiento <= :ahora')
            ->setParameter('pagadorId', $pagadorId)
            ->setParameter('estado', CuentaPorCobrar::PENDIENTE)
            ->setParameter('ahora', $ahora)
            ->getQuery()
            ->getResult();

        $obligaciones = [];
        foreach ($cuentas as $cuenta) {
            $vencimiento = $cuenta->getVencimiento();
            if (null !== $vencimiento && null !== $cuenta->getId()) {
                $obligaciones[] = [
                    'id' => $cuenta->getId(),
                    'vencimiento' => $vencimiento,
                ];
            }
        }

        return $obligaciones;
    }
}
