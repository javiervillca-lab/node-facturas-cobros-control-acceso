<?php

namespace App\control_acceso\application;

final readonly class PerfilMiembro
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $plan,
    ) {
        if ($id <= 0) {
            throw new \InvalidArgumentException('el perfil requiere un miembro persistido');
        }
    }
}
