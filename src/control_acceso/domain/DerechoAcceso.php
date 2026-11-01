<?php

namespace App\control_acceso\domain;

final readonly class DerechoAcceso
{
    public function __construct(
        public bool $vigente,
        public ?\DateTimeImmutable $vigenteHasta,
        public string $horario,
        public bool $simulado,
    ) {
        if (!in_array($horario, ['normal', 'continuo'], true)) {
            throw new \InvalidArgumentException('el horario del derecho de acceso no es válido');
        }
    }

    public function permiteIngreso(\DateTimeImmutable $ahora): bool
    {
        if (!$this->vigente || (null !== $this->vigenteHasta && $ahora >= $this->vigenteHasta)) {
            return false;
        }
        if ($this->horario === 'continuo') {
            return true;
        }

        $local = $ahora->setTimezone(new \DateTimeZone('America/La_Paz'));
        $dia = (int) $local->format('N');
        $hora = $local->format('H:i');

        return $dia >= 1 && $dia <= 7 && $hora >= '08:00' && $hora < '18:00';
    }
}
