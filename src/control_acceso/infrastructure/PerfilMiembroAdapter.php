<?php

namespace App\control_acceso\infrastructure;

use App\control_acceso\application\PerfilMiembro;
use App\control_acceso\application\PerfilMiembroPortInterface;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;

final class PerfilMiembroAdapter implements PerfilMiembroPortInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function consultar(array $miembroIds): array
    {
        $miembroIds = array_values(array_unique(array_filter($miembroIds, static fn (int $id): bool => $id > 0)));
        if ([] === $miembroIds) {
            return [];
        }

        $perfiles = [];
        foreach ($this->entityManager->getRepository(Cliente::class)->findBy(['id' => $miembroIds]) as $miembro) {
            $perfiles[$miembro->getId()] = new PerfilMiembro($miembro->getId(), $miembro->getNombre(), $miembro->getPlan());
        }

        return $perfiles;
    }
}
