<?php

namespace App\Tests\Command;

use App\Command\PrepareAccessDemoScenarioCommand;
use App\cobros\domain\CuentaPorCobrar;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PrepareAccessDemoScenarioCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }

        parent::tearDown();
    }

    public function testCommandCanBeRerunWithoutDuplicatingTheDemoScenario(): void
    {
        $command = static::getContainer()->get(PrepareAccessDemoScenarioCommand::class);
        $tester = new CommandTester($command);

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Preparado escenario de impago', $tester->getDisplay());

        $this->entityManager->clear();
        $cliente = $this->entityManager->getRepository(Cliente::class)->findOneBy([
            'email' => 'demo-acceso@nodo.example.test',
        ]);
        self::assertInstanceOf(Cliente::class, $cliente);
        $miembroId = $cliente->getId();
        self::assertNotNull($miembroId);

        $acceso = static::getContainer()->get(AccesoMiembroRepositoryInterface::class)->obtenerPorMiembro($miembroId);
        self::assertNotNull($acceso);

        $cuentas = $this->entityManager->getRepository(CuentaPorCobrar::class);
        $cuentasDemo = $cuentas->findBy(['cliente' => $cliente, 'periodo' => 'DEMO-ACCESO-IMPAGO']);
        self::assertCount(1, $cuentasDemo);
        $cuentaId = $cuentasDemo[0]->getId();
        self::assertSame(CuentaPorCobrar::PENDIENTE, $cuentasDemo[0]->getEstado());
        $vencimiento = $cuentasDemo[0]->getVencimiento();
        self::assertNotNull($vencimiento);
        self::assertLessThanOrEqual(
            new \DateTimeImmutable(),
            $vencimiento->modify('+5 days'),
        );

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Ya estaba preparado', $tester->getDisplay());

        $this->entityManager->clear();
        $cuentasDemo = $this->entityManager->getRepository(CuentaPorCobrar::class)
            ->findBy(['cliente' => $cliente, 'periodo' => 'DEMO-ACCESO-IMPAGO']);
        self::assertCount(1, $cuentasDemo);
        self::assertSame($cuentaId, $cuentasDemo[0]->getId());
    }
}
