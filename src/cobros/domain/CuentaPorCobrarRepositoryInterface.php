<?php

namespace App\cobros\domain;

use App\Entity\Cliente;

interface CuentaPorCobrarRepositoryInterface
{
    public function obtener(int $id): ?CuentaPorCobrar;

    public function guardar(CuentaPorCobrar $cuenta): void;

    /**
     * @return CuentaPorCobrar[]
     */
    public function todas(): array;

    /**
     * @return CuentaPorCobrar[]
     */
    public function pendientesDe(Cliente $cliente): array;
}
