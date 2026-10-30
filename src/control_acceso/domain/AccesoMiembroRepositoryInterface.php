<?php

namespace App\control_acceso\domain;

use App\Entity\Cliente;

interface AccesoMiembroRepositoryInterface
{
    public function obtener(int $id): ?AccesoMiembro;

    public function obtenerPorMiembro(Cliente $miembro): ?AccesoMiembro;

    public function guardar(AccesoMiembro $acceso): void;

    /**
     * @return AccesoMiembro[]
     */
    public function todos(): array;
}
