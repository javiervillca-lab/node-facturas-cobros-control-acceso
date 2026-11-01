<?php

namespace App\cobros\application;

use App\cobros\domain\CuentaPorCobrarRepositoryInterface;

class GestionarDevolucionService
{
    public function __construct(
        private CuentaPorCobrarRepositoryInterface $cuentas,
        private DevolucionPortInterface $devolucionPort,
    ) {
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

    public function confirmar(int $cuentaId, int $devolucionId): void
    {
        $cuenta = $this->cuentas->obtener($cuentaId);
        if (null === $cuenta) {
            throw new \DomainException('la cuenta no existe');
        }

        $devolucion = $cuenta->buscarDevolucion($devolucionId);
        if (null === $devolucion) {
            throw new \DomainException('la devolucion no existe en esta cuenta');
        }
        if ($devolucion->getEstado() === \App\cobros\domain\DevolucionExceso::DEVUELTA) {
            return;
        }

        $resultado = $this->devolucionPort->ejecutar($devolucion);
        $devolucion->confirmar($resultado);
        $this->cuentas->guardar($cuenta);
    }
}
