<?php

namespace App\cobros\domain;

final readonly class VerificacionPago
{
    public function __construct(
        public string $id,
        public string $referencia,
        public string $proveedor,
        public string $importe,
        public string $moneda,
        public int $pagadorId,
        public bool $simulada,
    ) {
        if ('' === trim($id) || strlen($id) > 100 || '' === trim($referencia) || '' === trim($proveedor)
            || strlen($referencia) > 80 || strlen($proveedor) > 80) {
            throw new \InvalidArgumentException('la verificación requiere identidad, referencia y proveedor');
        }
        if (Dinero::aCentavos($importe) <= 0) {
            throw new \InvalidArgumentException('el importe verificado debe ser mayor que cero');
        }
        if (!preg_match('/^[A-Z]{3}$/', $moneda) || $pagadorId <= 0) {
            throw new \InvalidArgumentException('la verificación requiere moneda y pagador válidos');
        }
    }
}
