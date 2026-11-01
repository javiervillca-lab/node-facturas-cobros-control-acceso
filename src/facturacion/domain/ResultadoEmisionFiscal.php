<?php

namespace App\facturacion\domain;

final readonly class ResultadoEmisionFiscal
{
    public function __construct(
        public string $referenciaFiscal,
        public string $total,
        public string $moneda,
        public bool $simulada,
    ) {
        if ('' === trim($referenciaFiscal) || strlen($referenciaFiscal) > 40 || !preg_match('/^[A-Z]{3}$/', $moneda)) {
            throw new \InvalidArgumentException('el resultado fiscal requiere referencia y moneda válidas');
        }
    }
}
