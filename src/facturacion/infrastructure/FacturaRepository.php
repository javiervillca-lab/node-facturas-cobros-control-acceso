<?php

namespace App\facturacion\infrastructure;

use App\cobros\domain\CuentaPorCobrar;
use App\facturacion\domain\Factura;
use App\facturacion\domain\FacturaRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class FacturaRepository implements FacturaRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function obtener(int $id): ?Factura
    {
        return $this->em->getRepository(Factura::class)->find($id);
    }

    public function obtenerPorCuenta(CuentaPorCobrar $cuenta): ?Factura
    {
        return $this->em->getRepository(Factura::class)->findOneBy(['cuenta' => $cuenta]);
    }

    public function guardar(Factura $factura): void
    {
        $this->em->persist($factura);
        $this->em->flush();
    }

    public function todas(): array
    {
        return $this->em->getRepository(Factura::class)->findBy([], ['id' => 'DESC']);
    }
}
