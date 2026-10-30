<?php

namespace App\control_acceso\domain;

class EvaluadorDeIngreso
{
    public const FAVORABLE = 'favorable';
    public const DESFAVORABLE = 'desfavorable';

    public static function evaluarSuspension(bool $tieneDeudaVencidaBloqueante): string
    {
        return $tieneDeudaVencidaBloqueante ? self::FAVORABLE : self::DESFAVORABLE;
    }

    public static function evaluarRestablecimiento(bool $tieneDeudaVencidaBloqueante): string
    {
        return $tieneDeudaVencidaBloqueante ? self::DESFAVORABLE : self::FAVORABLE;
    }

    public static function evaluarIngreso(AccesoMiembro $acceso, bool $tieneDeudaVencidaBloqueante): array
    {
        if ($acceso->getEstado() !== AccesoMiembro::HABILITADO) {
            return ['permitido' => false, 'motivo' => 'el acceso no esta habilitado'];
        }

        if ($tieneDeudaVencidaBloqueante) {
            return ['permitido' => false, 'motivo' => 'existe una deuda vencida bloqueante'];
        }

        return ['permitido' => true, 'motivo' => 'derecho vigente y situacion economica regularizada'];
    }
}
