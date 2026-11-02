<?php

namespace App\control_acceso\application;

use App\control_acceso\domain\EvidenciaRegistroHuella;
use App\control_acceso\domain\OperacionRegistroHuella;

interface RegistroHuellaPortInterface
{
    public function ejecutar(OperacionRegistroHuella $operacion): EvidenciaRegistroHuella;
}
