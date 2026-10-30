<?php

namespace App\cobros\domain;

class CalculoDeCobro
{
    /**
     * @param ConceptoCobro[] $conceptos
     */
    public static function totalDe(array $conceptos): float
    {
        $total = 0.0;
        foreach ($conceptos as $concepto) {
            $total += $concepto->getNeto();
        }

        return round($total, 2);
    }
}
