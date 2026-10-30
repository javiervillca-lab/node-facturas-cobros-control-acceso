<?php

namespace App\Command;

use App\cobros\application\LiquidarCuentaService;
use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed', description: 'Carga clientes y cuentas de ejemplo para probar el prototipo')]
class SeedDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private AccesoMiembroRepositoryInterface $accesos,
        private LiquidarCuentaService $liquidarCuenta,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maria = new Cliente('Maria Fernandez', 'maria@example.com', 'mensual_flexible');
        $jorge = new Cliente('Jorge Quispe', 'jorge@example.com', 'escritorio_dedicado');
        $equipo = new Cliente('Equipo Andina SRL', 'contacto@andina.example.com', 'plan_equipo');

        foreach ([$maria, $jorge, $equipo] as $cliente) {
            $this->em->persist($cliente);
        }
        $this->em->flush();

        foreach ([$maria, $jorge, $equipo] as $cliente) {
            $this->accesos->guardar(AccesoMiembro::registrarInicial($cliente));
        }

        $cuentaMaria = $this->liquidarCuenta->ejecutar($maria, [
            ['tipo' => 'cuota', 'descripcion' => 'cuota mensual flexible', 'cantidad' => 1, 'precio' => 400, 'descuento' => 0],
            ['tipo' => 'hora_adicional', 'descripcion' => 'horas de sala excedentes', 'cantidad' => 4, 'precio' => 20, 'descuento' => 0],
        ], '2026-11', new \DateTimeImmutable('-10 days'));

        $this->liquidarCuenta->ejecutar($jorge, [
            ['tipo' => 'cuota', 'descripcion' => 'escritorio dedicado noviembre', 'cantidad' => 1, 'precio' => 650, 'descuento' => 0],
        ], '2026-11', new \DateTimeImmutable('+5 days'));

        $this->liquidarCuenta->ejecutar($equipo, [
            ['tipo' => 'deposito', 'descripcion' => 'deposito evento de fin de ano', 'cantidad' => 1, 'precio' => 200, 'descuento' => 0],
        ], null, new \DateTimeImmutable('+15 days'));

        $io->success(sprintf('datos de ejemplo cargados: 3 clientes, cuenta %s con saldo pendiente vencido', $cuentaMaria->getCodigo()));

        return Command::SUCCESS;
    }
}
