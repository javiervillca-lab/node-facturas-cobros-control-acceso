<?php

namespace App\facturacion\application;

use App\facturacion\domain\Factura;
use App\facturacion\domain\ResultadoEnvioFactura;

interface EnvioFacturaPortInterface
{
    public function enviar(Factura $factura): ResultadoEnvioFactura;
}
