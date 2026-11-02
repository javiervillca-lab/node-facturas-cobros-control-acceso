<?php

namespace App\cobros\infrastructure;

use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\CuentaPorCobrarRepositoryInterface;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;

class CuentaPorCobrarRepository implements CuentaPorCobrarRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function obtener(int $id): ?CuentaPorCobrar
    {
        return $this->em->getRepository(CuentaPorCobrar::class)->find($id);
    }

    public function guardar(CuentaPorCobrar $cuenta): void
    {
        $this->em->persist($cuenta);
        $this->em->flush();
    }

    public function buscarPagoPorProveedorYReferencia(string $proveedor, string $referencia): ?\App\cobros\domain\AplicacionPago
    {
        return $this->em->getRepository(\App\cobros\domain\AplicacionPago::class)->findOneBy([
            'proveedor' => $proveedor,
            'referencia' => $referencia,
        ]);
    }

    public function todas(): array
    {
        return $this->em->getRepository(CuentaPorCobrar::class)->findBy([], ['id' => 'DESC']);
    }

    public function pendientesDe(Cliente $cliente): array
    {
        return $this->em->getRepository(CuentaPorCobrar::class)->findBy([
            'cliente' => $cliente,
            'estado' => CuentaPorCobrar::PENDIENTE,
        ]);
    }
}
