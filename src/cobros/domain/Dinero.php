<?php

namespace App\cobros\domain;

final class Dinero
{
    private const MAX_CENTAVOS = 9999999999;

    public static function aCentavos(string|int $importe): int
    {
        $valor = (string) $importe;
        if (!preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,2}))?$/', $valor, $partes)) {
            throw new \InvalidArgumentException('el importe debe ser decimal no negativo con hasta dos decimales');
        }

        $enteros = (int) $partes[1];
        $decimales = isset($partes[2]) ? (int) str_pad($partes[2], 2, '0') : 0;
        if ($enteros > intdiv(self::MAX_CENTAVOS, 100)) {
            throw new \InvalidArgumentException('el importe excede el máximo permitido');
        }

        return $enteros * 100 + $decimales;
    }

    public static function desdeCentavos(int $centavos): string
    {
        if ($centavos < 0 || $centavos > self::MAX_CENTAVOS) {
            throw new \InvalidArgumentException('el importe en centavos está fuera de rango');
        }

        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }

    public static function sumar(string|int $importe, string|int $adicional): string
    {
        $importeCentavos = self::aCentavos($importe);
        $adicionalCentavos = self::aCentavos($adicional);
        if ($adicionalCentavos > self::MAX_CENTAVOS - $importeCentavos) {
            throw new \InvalidArgumentException('la suma excede el máximo permitido');
        }

        return self::desdeCentavos($importeCentavos + $adicionalCentavos);
    }

    public static function multiplicar(string|int $cantidad, string|int $precio): string
    {
        $cantidadCentavos = self::aCentavos($cantidad);
        $precioCentavos = self::aCentavos($precio);
        if ($cantidadCentavos === 0 || $precioCentavos === 0) {
            return '0.00';
        }
        if ($cantidadCentavos > intdiv(PHP_INT_MAX - 50, $precioCentavos)) {
            throw new \InvalidArgumentException('el cálculo del concepto excede el rango permitido');
        }

        $producto = $cantidadCentavos * $precioCentavos;
        $centavos = intdiv($producto + 50, 100);

        return self::desdeCentavos($centavos);
    }
}
