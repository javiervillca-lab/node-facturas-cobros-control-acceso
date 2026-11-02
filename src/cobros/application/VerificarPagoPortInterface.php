<?php

namespace App\cobros\application;

use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\VerificacionPago;

interface VerificarPagoPortInterface
{
    public function verificar(CuentaPorCobrar $cuenta, string $referencia, string $proveedor, string $importeInformado): VerificacionPago;
}
