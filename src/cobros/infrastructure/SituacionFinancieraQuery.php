<?php

namespace App\cobros\infrastructure;

use App\cobros\domain\CuentaPorCobrar;
use App\Entity\Cliente;
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

    public function tieneDeudaVencidaBloqueante(Cliente $cliente, \DateTimeImmutable $ahora): bool
    {
        $limite = $ahora->modify('-5 days');

        $cuentas = $this->em->getRepository(CuentaPorCobrar::class)->findBy([
            'cliente' => $cliente,
            'estado' => CuentaPorCobrar::PENDIENTE,
        ]);

        foreach ($cuentas as $cuenta) {
            $vencimiento = $cuenta->getVencimiento();
            if (null !== $vencimiento && $vencimiento <= $limite) {
                return true;
            }
        }

        return false;
    }
}
