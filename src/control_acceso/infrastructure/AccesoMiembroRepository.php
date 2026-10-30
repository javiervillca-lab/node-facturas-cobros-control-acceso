<?php

namespace App\control_acceso\infrastructure;

use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;

class AccesoMiembroRepository implements AccesoMiembroRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function obtener(int $id): ?AccesoMiembro
    {
        return $this->em->getRepository(AccesoMiembro::class)->find($id);
    }

    public function obtenerPorMiembro(Cliente $miembro): ?AccesoMiembro
    {
        return $this->em->getRepository(AccesoMiembro::class)->findOneBy(['miembro' => $miembro]);
    }

    public function guardar(AccesoMiembro $acceso): void
    {
        $this->em->persist($acceso);
        $this->em->flush();
    }

    public function todos(): array
    {
        return $this->em->getRepository(AccesoMiembro::class)->findBy([], ['id' => 'ASC']);
    }
}
