<?php

namespace App\facturacion\application;

use App\facturacion\domain\Factura;
use App\facturacion\domain\ResultadoEmisionFiscal;

interface EmisionFiscalPortInterface
{
    public function emitir(Factura $factura): ResultadoEmisionFiscal;
}
