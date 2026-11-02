<?php

namespace App\Command;

use App\cobros\application\LiquidarCuentaService;
use App\cobros\domain\CuentaPorCobrar;
use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\control_acceso\domain\AvisoPrevio;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:demo:preparar-acceso',
    description: 'Prepara de forma idempotente el escenario de impago para la demo de Control de Acceso',
)]
final class PrepareAccessDemoScenarioCommand extends Command
{
    private const EMAIL = 'demo-acceso@nodo.example.test';
    private const PERIODO = 'DEMO-ACCESO-IMPAGO';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccesoMiembroRepositoryInterface $accesos,
        private LiquidarCuentaService $liquidarCuenta,
        #[Autowire('%kernel.environment%')]
        private string $environment,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ('prod' === $this->environment) {
            $io->error('Este escenario de demostración no se puede preparar en producción.');

            return Command::FAILURE;
        }
        /*if (!in_array($this->entityManager->getConnection()->getDatabase(), ['faccob_demo', 'faccob_test'], true)) {
            $io->error('El escenario solo se puede preparar en faccob_demo (o faccob_test durante las pruebas).');

            return Command::FAILURE;
        }*/

        $clientes = $this->entityManager->getRepository(Cliente::class);
        $cliente = $clientes->findOneBy(['email' => self::EMAIL]);
        $clienteCreado = null === $cliente;
        if (null === $cliente) {
            $cliente = new Cliente('Miembro demo de acceso', self::EMAIL, 'mensual_flexible');
            $this->entityManager->persist($cliente);
            $this->entityManager->flush();
        } elseif ($cliente->getPlan() !== 'mensual_flexible') {
            $io->error('El correo reservado para la demo ya existe con un plan diferente.');

            return Command::FAILURE;
        }

        $clienteId = $cliente->getId();
        if (null === $clienteId) {
            throw new \LogicException('el miembro demo debe estar persistido');
        }

        $acceso = $this->accesos->obtenerPorMiembro($clienteId);
        if (null !== $acceso && (
            $acceso->getEstado() !== AccesoMiembro::HABILITADO
            || !$acceso->getOperaciones()->isEmpty()
            || $this->entityManager->getRepository(AvisoPrevio::class)->count(['acceso' => $acceso]) > 0
        )) {
            $io->error('El escenario ya avanzó de su estado inicial. No se modificó ni se creó otro escenario.');

            return Command::FAILURE;
        }

        $cuentas = $this->entityManager->getRepository(CuentaPorCobrar::class);
        $cuenta = $cuentas->findOneBy(['cliente' => $cliente, 'periodo' => self::PERIODO]);
        if (null !== $cuenta && (
            $cuenta->getEstado() !== CuentaPorCobrar::PENDIENTE
            || null === $cuenta->getVencimiento()
            || $cuenta->getVencimiento()->modify('+5 days') > new \DateTimeImmutable()
        )) {
            $io->error(sprintf(
                'La cuenta de demo %s ya existe, pero no está vencida e impaga según la regla de suspensión. No se creó otra.',
                $cuenta->getCodigo(),
            ));

            return Command::FAILURE;
        }

        $accesoCreado = null === $acceso;
        if (null === $acceso) {
            $acceso = AccesoMiembro::registrarInicial($clienteId);
            $this->accesos->guardar($acceso);
        }

        $cuentaCreada = null === $cuenta;
        if (null === $cuenta) {
            $cuenta = $this->liquidarCuenta->ejecutar(
                $cliente,
                [[
                    'tipo' => 'cuota',
                    'descripcion' => 'Cuota vencida para la demostración del flujo de acceso',
                    'cantidad' => '1',
                    'precio' => '100.00',
                ]],
                self::PERIODO,
                new \DateTimeImmutable('-7 days'),
            );
        }
        if (null === $cuenta->getId()) {
            throw new \LogicException('la cuenta demo debe quedar persistida');
        }

        $accesoId = $acceso->getId();
        if (null === $accesoId) {
            throw new \LogicException('el acceso demo debe quedar persistido');
        }

        $io->success(sprintf(
            "%s escenario de impago para Control de Acceso.\nMiembro: %s (%s)\nAcceso: /accesos/%d\nCuenta: %s (saldo BOB %s, vencimiento %s)",
            $clienteCreado || $accesoCreado || $cuentaCreada ? 'Preparado' : 'Ya estaba preparado',
            $cliente->getNombre(),
            $cliente->getEmail(),
            $accesoId,
            $cuenta->getCodigo(),
            $cuenta->getSaldo(),
            $cuenta->getVencimiento()?->format('Y-m-d H:i'),
        ));

        return Command::SUCCESS;
    }
}
