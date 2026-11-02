<?php

namespace App\control_acceso\infrastructure;

use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\AvisoPrevio;
use App\control_acceso\domain\AvisoPrevioRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class AvisoPrevioRepository implements AvisoPrevioRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function buscarMasReciente(AccesoMiembro $acceso, int $pagadorId, array $obligacionIds): ?AvisoPrevio
    {
        sort($obligacionIds);
        $avisos = $this->em->getRepository(AvisoPrevio::class)->findBy(
            ['acceso' => $acceso],
            ['enviadoEn' => 'DESC'],
        );

        foreach ($avisos as $aviso) {
            if ($aviso->correspondeA($pagadorId, $obligacionIds)) {
                return $aviso;
            }
        }

        return null;
    }

    public function guardar(AvisoPrevio $aviso): void
    {
        $this->em->persist($aviso);
        $this->em->flush();
    }
}
