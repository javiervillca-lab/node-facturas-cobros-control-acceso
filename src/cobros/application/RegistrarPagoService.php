<?php

namespace App\cobros\application;

use App\cobros\domain\AplicacionPago;
use App\cobros\domain\CuentaPorCobrarRepositoryInterface;

class RegistrarPagoService
{
    public function __construct(private CuentaPorCobrarRepositoryInterface $cuentas)
    {
    }

    public function ejecutar(int $cuentaId, string $referencia, string $proveedor, float $importe): AplicacionPago
    {
        $cuenta = $this->cuentas->obtener($cuentaId);
        if (null === $cuenta) {
            throw new \DomainException('la cuenta no existe');
        }

        $aplicacion = $cuenta->registrarPagoConfirmado($referencia, $proveedor, $importe);
        $this->cuentas->guardar($cuenta);

        return $aplicacion;
    }
}
