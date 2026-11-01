<?php

namespace App\cobros\domain;

class CalculoDeCobro
{
    /**
     * @param ConceptoCobro[] $conceptos
     */
    public static function totalDe(array $conceptos): string
    {
        $total = '0.00';
        foreach ($conceptos as $concepto) {
            $total = Dinero::sumar($total, $concepto->getNeto());
        }

        return $total;
    }
}
