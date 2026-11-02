<?php

namespace App\control_acceso\application;

interface PerfilMiembroPortInterface
{
    /**
     * @param list<int> $miembroIds
     *
     * @return array<int, PerfilMiembro>
     */
    public function consultar(array $miembroIds): array;
}
