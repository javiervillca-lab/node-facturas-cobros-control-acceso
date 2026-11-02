<?php

namespace App\control_acceso\domain;

final readonly class SituacionPago
{
    /**
     * @param array<int, array{id: int, vencimiento: \DateTimeImmutable}> $obligacionesVencidas
     */
    public function __construct(
        public int $pagadorId,
        public array $obligacionesVencidas,
        public \DateTimeImmutable $consultadaEn,
    ) {
        if ($pagadorId <= 0) {
            throw new \InvalidArgumentException('la situación de pago requiere pagador persistido');
        }
        foreach ($obligacionesVencidas as $obligacion) {
            if ($obligacion['id'] <= 0 || !$obligacion['vencimiento'] instanceof \DateTimeImmutable) {
                throw new \InvalidArgumentException('la situación contiene una obligación inválida');
            }
        }
    }

    /**
     * @return list<int>
     */
    public function idsObligacionesBloqueantes(\DateTimeImmutable $ahora): array
    {
        $ids = [];
        foreach ($this->obligacionesVencidas as $obligacion) {
            if ($obligacion['vencimiento']->modify('+5 days') <= $ahora) {
                $ids[] = $obligacion['id'];
            }
        }
        sort($ids);

        return array_values(array_unique($ids));
    }

    public function tieneObligacionBloqueante(\DateTimeImmutable $ahora): bool
    {
        foreach ($this->obligacionesVencidas as $obligacion) {
            if ($obligacion['vencimiento']->modify('+5 days') <= $ahora) {
                return true;
            }
        }

        return false;
    }
}
