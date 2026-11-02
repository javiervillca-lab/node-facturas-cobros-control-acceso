<?php

namespace App\cobros\infrastructure;

use App\cobros\application\VerificarPagoPortInterface;
use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\Dinero;
use App\cobros\domain\VerificacionPago;

final class SimulatedPaymentVerificationAdapter implements VerificarPagoPortInterface
{
    public function __construct(private string $environment)
    {
    }

    public function verificar(CuentaPorCobrar $cuenta, string $referencia, string $proveedor, string $importeInformado): VerificacionPago
    {
        if ('prod' === $this->environment) {
            throw new \RuntimeException('la simulación de pagos está deshabilitada en producción; configure un adaptador real');
        }
        if (null === $cuenta->getCliente()->getId()) {
            throw new \LogicException('no se puede simular el pago de una cuenta sin pagador persistido');
        }

        return new VerificacionPago(
            sprintf('SIM-PAGO-%s', hash('sha256', $proveedor . ':' . $referencia . ':' . $cuenta->getId())),
            $referencia,
            $proveedor,
            Dinero::desdeCentavos(Dinero::aCentavos($importeInformado)),
            $cuenta->getMoneda(),
            $cuenta->getCliente()->getId(),
            true,
        );
    }
}
