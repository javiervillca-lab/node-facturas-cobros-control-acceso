<?php

namespace App\control_acceso\domain;

final readonly class ResultadoAviso
{
    public function __construct(
        public string $referencia,
        public \DateTimeImmutable $enviadoEn,
        public bool $simulado,
    ) {
        if ('' === trim($referencia) || strlen($referencia) > 120) {
            throw new \InvalidArgumentException('el aviso requiere una constancia de entrega');
        }
    }
}
