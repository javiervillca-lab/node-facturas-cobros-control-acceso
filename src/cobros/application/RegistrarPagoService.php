<?php

namespace App\cobros\application;

use App\cobros\domain\AplicacionPago;
use App\cobros\domain\CuentaPorCobrarRepositoryInterface;

class RegistrarPagoService
{
    public function __construct(
        private CuentaPorCobrarRepositoryInterface $cuentas,
        private VerificarPagoPortInterface $verificador,
    ) {
    }

    public function ejecutar(int $cuentaId, string $referencia, string $proveedor, string $importeInformado): AplicacionPago
    {
        $cuenta = $this->cuentas->obtener($cuentaId);
        if (null === $cuenta) {
            throw new \DomainException('la cuenta no existe');
        }

        $verificacion = $this->verificador->verificar($cuenta, $referencia, $proveedor, $importeInformado);
        if ($verificacion->moneda !== $cuenta->getMoneda()
            || $verificacion->pagadorId !== $cuenta->getCliente()->getId()) {
            throw new \DomainException('la verificación no coincide con la moneda o el pagador de la cuenta');
        }
        $existente = $this->cuentas->buscarPagoPorProveedorYReferencia($verificacion->proveedor, $verificacion->referencia);
        if (null !== $existente) {
            if ($existente->getCuenta()->getId() !== $cuenta->getId()
                || $existente->getImporteRecibido() !== $verificacion->importe
                || $existente->esSimulada() !== $verificacion->simulada) {
                throw new \DomainException('la referencia de pago ya fue usada con datos incompatibles');
            }

            return $existente;
        }

        $aplicacion = $cuenta->registrarPagoVerificado($verificacion);
        $this->cuentas->guardar($cuenta);

        return $aplicacion;
    }
}
