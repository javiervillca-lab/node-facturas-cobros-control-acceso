<?php

namespace App\control_acceso\domain;

interface AvisoPrevioRepositoryInterface
{
    /**
     * @param list<int> $obligacionIds
     */
    public function buscarMasReciente(AccesoMiembro $acceso, int $pagadorId, array $obligacionIds): ?AvisoPrevio;

    public function guardar(AvisoPrevio $aviso): void;
}
