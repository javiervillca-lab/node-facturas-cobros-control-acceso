<?php

namespace App\Tests\integration;

use App\Entity\Cliente;
use App\cobros\application\GestionarDevolucionService;
use App\cobros\application\LiquidarCuentaService;
use App\cobros\application\RegistrarPagoService;
use App\cobros\domain\AplicacionPago;
use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\DevolucionExceso;
use App\control_acceso\application\PerfilMiembroPortInterface;
use App\control_acceso\application\RestablecerAccesoService;
use App\control_acceso\application\SuspenderAccesoService;
use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\control_acceso\domain\AvisoPrevio;
use App\control_acceso\domain\OperacionRegistroHuella;
use App\facturacion\application\EmitirFacturaService;
use App\facturacion\application\EnviarFacturaService;
use App\facturacion\domain\Factura;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SimulatedFlowsIntegrationTest extends KernelTestCase
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

    public function testPaymentIsAppliedOnlyOnceForRepeatedProviderReference(): void
    {
        $cuenta = $this->crearCuenta('100.00');
        $registrarPago = static::getContainer()->get(RegistrarPagoService::class);

        $primeraAplicacion = $registrarPago->ejecutar($cuenta->getId(), 'TX-INTEGRACION-1', 'simulador', '40.00');
        $segundaAplicacion = $registrarPago->ejecutar($cuenta->getId(), 'TX-INTEGRACION-1', 'simulador', '40.00');

        self::assertSame($primeraAplicacion->getId(), $segundaAplicacion->getId());
        self::assertSame(1, $this->entityManager->getRepository(AplicacionPago::class)->count([]));

        $this->entityManager->clear();
        $cuentaPersistida = $this->entityManager->getRepository(CuentaPorCobrar::class)->find($cuenta->getId());
        self::assertSame('60.00', $cuentaPersistida->getSaldo());
    }

    public function testProviderReferenceCannotBeReusedForAnotherAccount(): void
    {
        $primeraCuenta = $this->crearCuenta('100.00');
        $segundaCuenta = $this->crearCuenta('100.00');
        $registrarPago = static::getContainer()->get(RegistrarPagoService::class);
        $registrarPago->ejecutar($primeraCuenta->getId(), 'TX-INTEGRACION-2', 'simulador', '40.00');

        try {
            $registrarPago->ejecutar($segundaCuenta->getId(), 'TX-INTEGRACION-2', 'simulador', '40.00');
            self::fail('Debe rechazarse la reutilización global de una referencia de pago.');
        } catch (\DomainException) {
            self::assertSame(1, $this->entityManager->getRepository(AplicacionPago::class)->count([]));
        }
    }

    public function testExcessRefundIsApprovedExecutedAndPersistedAsSimulated(): void
    {
        $cuenta = $this->crearCuenta('100.00');
        $this->pagar($cuenta, 'TX-INTEGRACION-3', '120.00');
        $this->entityManager->clear();
        $cuenta = $this->entityManager->getRepository(CuentaPorCobrar::class)->find($cuenta->getId());
        $devolucion = $cuenta->getDevoluciones()->first();

        self::assertInstanceOf(DevolucionExceso::class, $devolucion);
        $gestionarDevolucion = static::getContainer()->get(GestionarDevolucionService::class);
        $gestionarDevolucion->aprobar($cuenta->getId(), $devolucion->getId(), 'Operador de prueba');
        $gestionarDevolucion->confirmar($cuenta->getId(), $devolucion->getId());

        $this->entityManager->clear();
        $devolucionPersistida = $this->entityManager->getRepository(DevolucionExceso::class)->find($devolucion->getId());
        self::assertSame(DevolucionExceso::DEVUELTA, $devolucionPersistida->getEstado());
        self::assertTrue($devolucionPersistida->esSimulada());
        self::assertStringStartsWith('SIM-DEV-', $devolucionPersistida->getReferenciaSalida());
    }

    public function testInvoiceIssuanceAndDeliveryArePersistedAsSimulated(): void
    {
        $cuenta = $this->crearCuenta('100.00');
        $this->pagar($cuenta, 'TX-INTEGRACION-4', '100.00');
        $emitir = static::getContainer()->get(EmitirFacturaService::class);
        $factura = $emitir->preparar($cuenta->getId());
        $factura = $emitir->confirmarEmision($factura->getId());

        self::assertSame(Factura::EMITIDA, $factura->getEstado());
        self::assertTrue($factura->esEmisionSimulada());
        self::assertStringStartsWith('SIM-SIN-', $factura->getReferenciaFiscal());

        $enviar = static::getContainer()->get(EnviarFacturaService::class);
        $factura = $enviar->ejecutar($factura->getId());

        self::assertSame(Factura::ENVIADA, $factura->getEstado());
        self::assertTrue($factura->esEnvioSimulado());
        self::assertStringStartsWith('SIM-ENVIO-', $factura->getConstanciaEnvio());

        $this->entityManager->clear();
        $facturaPersistida = $this->entityManager->getRepository(Factura::class)->find($factura->getId());
        self::assertSame(Factura::ENVIADA, $facturaPersistida->getEstado());
        self::assertTrue($facturaPersistida->esEmisionSimulada());
        self::assertTrue($facturaPersistida->esEnvioSimulado());
    }

    public function testAccessStoresMemberIdentityAndReadsProfileThroughItsPort(): void
    {
        $cliente = new Cliente('Miembro de integración', 'miembro-integracion@example.test', 'plan-demo');
        $this->entityManager->persist($cliente);
        $this->entityManager->flush();

        $acceso = AccesoMiembro::registrarInicial($cliente->getId());
        static::getContainer()->get(AccesoMiembroRepositoryInterface::class)->guardar($acceso);

        $perfiles = static::getContainer()->get(PerfilMiembroPortInterface::class);
        $perfil = $perfiles->consultar([$cliente->getId()])[$cliente->getId()];
        self::assertSame('Miembro de integración', $perfil->nombre);

        $miembroId = $cliente->getId();
        $accesoId = $acceso->getId();
        $this->entityManager->remove($cliente);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $accesoPersistido = $this->entityManager->getRepository(AccesoMiembro::class)->find($accesoId);
        self::assertSame($miembroId, $accesoPersistido->getMiembroId());
    }

    public function testAccessSuspensionWaitsForNoticeAndCanBeRestoredAfterPayment(): void
    {
        ['acceso' => $acceso, 'cuenta' => $cuenta] = $this->crearAccesoConDeudaBloqueante();
        $suspender = static::getContainer()->get(SuspenderAccesoService::class);

        $avisoEnviado = $suspender->evaluarYSolicitar($acceso->getId());
        self::assertFalse($avisoEnviado['solicitada']);
        self::assertTrue($avisoEnviado['avisoEnviado']);

        $this->entityManager->clear();
        $acceso = $this->entityManager->getRepository(AccesoMiembro::class)->find($acceso->getId());
        self::assertSame(AccesoMiembro::HABILITADO, $acceso->getEstado());
        self::assertSame(1, $this->entityManager->getRepository(AvisoPrevio::class)->count([]));
        self::assertTrue($this->entityManager->getRepository(AvisoPrevio::class)->findOneBy([])->esSimulado());

        $avisoPendiente = $suspender->evaluarYSolicitar($acceso->getId());
        self::assertFalse($avisoPendiente['solicitada']);
        self::assertTrue($avisoPendiente['esperaAviso']);
        self::assertSame(1, $this->entityManager->getRepository(AvisoPrevio::class)->count([]));

        $aviso = $this->entityManager->getRepository(AvisoPrevio::class)->findOneBy([]);
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE acceso_aviso_previo SET enviado_en = :enviadoEn WHERE id = :id',
            ['enviadoEn' => (new \DateTimeImmutable('-23 hours'))->format('Y-m-d H:i:s'), 'id' => $aviso->getId()],
        );
        $this->entityManager->clear();

        $avisoAunVigente = $suspender->evaluarYSolicitar($acceso->getId());
        self::assertFalse($avisoAunVigente['solicitada']);
        self::assertTrue($avisoAunVigente['esperaAviso']);
        self::assertSame(1, $this->entityManager->getRepository(AvisoPrevio::class)->count([]));

        $aviso = $this->entityManager->getRepository(AvisoPrevio::class)->findOneBy([]);
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE acceso_aviso_previo SET enviado_en = :enviadoEn WHERE id = :id',
            ['enviadoEn' => (new \DateTimeImmutable('-25 hours'))->format('Y-m-d H:i:s'), 'id' => $aviso->getId()],
        );
        $this->entityManager->clear();

        $suspensionSolicitada = $suspender->evaluarYSolicitar($acceso->getId());
        self::assertTrue($suspensionSolicitada['solicitada']);
        self::assertSame(1, $this->entityManager->getRepository(AvisoPrevio::class)->count([]));

        $suspender->confirmar($acceso->getId(), $suspensionSolicitada['operacionId']);
        $this->entityManager->clear();
        $acceso = $this->entityManager->getRepository(AccesoMiembro::class)->find($acceso->getId());
        self::assertSame(AccesoMiembro::SUSPENDIDO, $acceso->getEstado());

        $retiro = $acceso->getOperaciones()->first();
        self::assertSame(OperacionRegistroHuella::CONFIRMADA, $retiro->getEstado());
        self::assertTrue($retiro->esSimulada());
        self::assertStringStartsWith('SIM-HUELLA-', $retiro->getEvidenciaId());

        $this->pagar($cuenta, 'TX-ACCESO-REGULARIZACION', '100.00');
        $restablecer = static::getContainer()->get(RestablecerAccesoService::class);
        $restablecimientoSolicitado = $restablecer->evaluarYSolicitar($acceso->getId(), 'Operador de prueba');
        self::assertTrue($restablecimientoSolicitado['solicitada']);

        $restablecer->confirmar($acceso->getId(), $restablecimientoSolicitado['operacionId']);
        $this->entityManager->clear();
        $acceso = $this->entityManager->getRepository(AccesoMiembro::class)->find($acceso->getId());
        self::assertSame(AccesoMiembro::HABILITADO, $acceso->getEstado());
        self::assertCount(2, $acceso->getOperaciones());

        foreach ($acceso->getOperaciones() as $operacion) {
            self::assertSame(OperacionRegistroHuella::CONFIRMADA, $operacion->getEstado());
            self::assertTrue($operacion->esSimulada());
        }
    }

    private function crearCuenta(string $total): CuentaPorCobrar
    {
        $cliente = new Cliente(
            'Cliente de integración',
            sprintf('integracion-%s@example.test', bin2hex(random_bytes(6))),
            'normal',
        );
        $this->entityManager->persist($cliente);
        $this->entityManager->flush();

        return static::getContainer()->get(LiquidarCuentaService::class)->ejecutar(
            $cliente,
            [['tipo' => 'membresia', 'descripcion' => 'Prueba de integración', 'cantidad' => '1', 'precio' => $total]],
            '2026-11',
            null,
        );
    }

    private function pagar(CuentaPorCobrar $cuenta, string $referencia, string $importe): void
    {
        static::getContainer()->get(RegistrarPagoService::class)->ejecutar(
            $cuenta->getId(),
            $referencia,
            'simulador',
            $importe,
        );
    }

    /**
     * @return array{acceso: AccesoMiembro, cuenta: CuentaPorCobrar}
     */
    private function crearAccesoConDeudaBloqueante(): array
    {
        $cliente = new Cliente(
            'Miembro con deuda',
            sprintf('deuda-%s@example.test', bin2hex(random_bytes(6))),
            'plan-demo',
        );
        $this->entityManager->persist($cliente);
        $this->entityManager->flush();

        $acceso = AccesoMiembro::registrarInicial($cliente->getId());
        static::getContainer()->get(AccesoMiembroRepositoryInterface::class)->guardar($acceso);

        $cuenta = static::getContainer()->get(LiquidarCuentaService::class)->ejecutar(
            $cliente,
            [['tipo' => 'membresia', 'descripcion' => 'Obligación vencida', 'cantidad' => '1', 'precio' => '100.00']],
            '2026-10',
            new \DateTimeImmutable('-7 days'),
        );

        return ['acceso' => $acceso, 'cuenta' => $cuenta];
    }
}
