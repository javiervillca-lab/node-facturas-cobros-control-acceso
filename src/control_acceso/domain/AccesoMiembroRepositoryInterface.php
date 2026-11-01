<?php

namespace App\control_acceso\domain;

interface AccesoMiembroRepositoryInterface
{
    public function obtener(int $id): ?AccesoMiembro;

    public function obtenerPorMiembro(int $miembroId): ?AccesoMiembro;

    public function guardar(AccesoMiembro $acceso): void;

    /**
     * @return AccesoMiembro[]
     */
    public function todos(): array;
}
