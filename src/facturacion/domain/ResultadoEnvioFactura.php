<?php

namespace App\facturacion\domain;

final readonly class ResultadoEnvioFactura
{
    public function __construct(public string $constancia, public bool $simulado)
    {
        if ('' === trim($constancia) || strlen($constancia) > 120) {
            throw new \InvalidArgumentException('el resultado de envío requiere una constancia');
        }
    }
}
