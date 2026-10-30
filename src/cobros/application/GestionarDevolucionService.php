<?php

namespace App\cobros\application;

use App\cobros\domain\CuentaPorCobrarRepositoryInterface;

class GestionarDevolucionService
{
    public function __construct(private CuentaPorCobrarRepositoryInterface $cuentas)
    {
    }

    public function aprobar(int $cuentaId, int $devolucionId, string $autorizadoPor): void
    {
        $cuenta = $this->cuentas->obtener($cuentaId);
        if (null === $cuenta) {
            throw new \DomainException('la cuenta no existe');
        }

        $devolucion = $cuenta->buscarDevolucion($devolucionId);
        if (null === $devolucion) {
            throw new \DomainException('la devolucion no existe en esta cuenta');
        }

        $devolucion->aprobar($autorizadoPor);
        $this->cuentas->guardar($cuenta);
    }

    public function confirmar(int $cuentaId, int $devolucionId, string $referenciaSalida): void
    {
        $cuenta = $this->cuentas->obtener($cuentaId);
        if (null === $cuenta) {
            throw new \DomainException('la cuenta no existe');
        }

        $devolucion = $cuenta->buscarDevolucion($devolucionId);
        if (null === $devolucion) {
            throw new \DomainException('la devolucion no existe en esta cuenta');
        }

        $devolucion->confirmar($referenciaSalida);
        $this->cuentas->guardar($cuenta);
    }
}
