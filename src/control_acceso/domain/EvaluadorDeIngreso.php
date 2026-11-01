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

    public static function evaluarIngreso(AccesoMiembro $acceso, bool $tieneDeudaVencidaBloqueante, DerechoAcceso $derecho, \DateTimeImmutable $ahora): array
    {
        if ($acceso->getEstado() !== AccesoMiembro::HABILITADO) {
            return ['permitido' => false, 'motivo' => 'el acceso no esta habilitado'];
        }

        if (!$derecho->permiteIngreso($ahora)) {
            return ['permitido' => false, 'motivo' => 'el derecho no está vigente o el ingreso está fuera de horario', 'simulada' => $derecho->simulado];
        }

        if ($tieneDeudaVencidaBloqueante) {
            return ['permitido' => false, 'motivo' => 'existe una deuda vencida bloqueante', 'simulada' => $derecho->simulado];
        }

        return ['permitido' => true, 'motivo' => 'derecho vigente, horario permitido y situación económica regularizada', 'simulada' => $derecho->simulado];
    }
}
