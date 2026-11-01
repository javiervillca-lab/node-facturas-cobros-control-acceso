<?php

namespace App\cobros\domain;

final readonly class ResultadoDevolucion
{
    public function __construct(public string $referencia, public bool $simulada)
    {
        if ('' === trim($referencia) || strlen($referencia) > 80) {
            throw new \InvalidArgumentException('la devolución requiere una referencia de salida');
        }
    }
}
