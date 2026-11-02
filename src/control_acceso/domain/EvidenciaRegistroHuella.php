<?php

namespace App\control_acceso\domain;

final readonly class EvidenciaRegistroHuella
{
    public function __construct(public string $id, public bool $simulada)
    {
        if ('' === trim($id) || strlen($id) > 120) {
            throw new \InvalidArgumentException('la evidencia de huella requiere una identidad');
        }
    }
}
