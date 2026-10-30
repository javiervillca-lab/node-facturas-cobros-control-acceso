<?php

namespace App\cobros\interfaces;

use App\cobros\application\GestionarDevolucionService;
use App\cobros\application\LiquidarCuentaService;
use App\cobros\application\RegistrarPagoService;
use App\cobros\domain\CuentaPorCobrarRepositoryInterface;
use App\Repository\ClienteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CuentaController extends AbstractController
{
    #[Route('/cuentas', name: 'cuenta_index', methods: ['GET'])]
    public function index(CuentaPorCobrarRepositoryInterface $cuentas): Response
    {
        return $this->render('cuenta/index.html.twig', [
            'cuentas' => $cuentas->todas(),
        ]);
    }

    #[Route('/cuentas/nueva', name: 'cuenta_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ClienteRepository $clientes, LiquidarCuentaService $liquidarCuenta): Response
    {
        if ($request->isMethod('POST')) {
            $this->validarCsrf($request);

            $clienteId = (int) $request->request->get('cliente');
            $cliente = $clientes->find($clienteId);
            if (null === $cliente) {
                $this->addFlash('error', 'selecciona un cliente valido');

                return $this->redirectToRoute('cuenta_new');
            }

            $tipos = $request->request->all('tipo');
            $descripciones = $request->request->all('descripcion');
            $cantidades = $request->request->all('cantidad');
            $precios = $request->request->all('precio');
            $descuentos = $request->request->all('descuento');

            $conceptos = [];
            foreach ($tipos as $i => $tipo) {
                if ('' === trim((string) $tipo)) {
                    continue;
                }
                $conceptos[] = [
                    'tipo' => $tipo,
                    'descripcion' => $descripciones[$i] ?? $tipo,
                    'cantidad' => $cantidades[$i] ?? 1,
                    'precio' => $precios[$i] ?? 0,
                    'descuento' => $descuentos[$i] ?? 0,
                ];
            }

            $vencimientoRaw = (string) $request->request->get('vencimiento');
            $vencimiento = '' !== $vencimientoRaw ? new \DateTimeImmutable($vencimientoRaw) : null;

            try {
                $cuenta = $liquidarCuenta->ejecutar($cliente, $conceptos, (string) $request->request->get('periodo') ?: null, $vencimiento);
            } catch (\DomainException|\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('cuenta_new');
            }

            $this->addFlash('success', sprintf('cuenta %s liquidada por %.2f', $cuenta->getCodigo(), $cuenta->getTotal()));

            return $this->redirectToRoute('cuenta_show', ['id' => $cuenta->getId()]);
        }

        return $this->render('cuenta/new.html.twig', [
            'clientes' => $clientes->findAll(),
        ]);
    }

    #[Route('/cuentas/{id}', name: 'cuenta_show', methods: ['GET'])]
    public function show(int $id, CuentaPorCobrarRepositoryInterface $cuentas): Response
    {
        $cuenta = $cuentas->obtener($id);
        if (null === $cuenta) {
            throw $this->createNotFoundException('cuenta no encontrada');
        }

        return $this->render('cuenta/show.html.twig', [
            'cuenta' => $cuenta,
        ]);
    }

    #[Route('/cuentas/{id}/pagos', name: 'cuenta_pago', methods: ['POST'])]
    public function registrarPago(int $id, Request $request, RegistrarPagoService $registrarPago): Response
    {
        $this->validarCsrf($request);

        try {
            $aplicacion = $registrarPago->ejecutar(
                $id,
                (string) $request->request->get('referencia'),
                (string) $request->request->get('proveedor'),
                (float) $request->request->get('importe'),
            );
            $mensaje = sprintf('pago aplicado: %.2f', $aplicacion->getImporteAplicado());
            if ($aplicacion->getExceso() > 0) {
                $mensaje .= sprintf(', exceso detectado: %.2f', $aplicacion->getExceso());
            }
            $this->addFlash('success', $mensaje);
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('cuenta_show', ['id' => $id]);
    }

    #[Route('/cuentas/{id}/devoluciones/{devolucionId}/aprobar', name: 'cuenta_devolucion_aprobar', methods: ['POST'])]
    public function aprobarDevolucion(int $id, int $devolucionId, Request $request, GestionarDevolucionService $gestionarDevolucion): Response
    {
        $this->validarCsrf($request);

        try {
            $gestionarDevolucion->aprobar($id, $devolucionId, (string) $request->request->get('autorizadoPor'));
            $this->addFlash('success', 'devolucion aprobada por direccion');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('cuenta_show', ['id' => $id]);
    }

    #[Route('/cuentas/{id}/devoluciones/{devolucionId}/confirmar', name: 'cuenta_devolucion_confirmar', methods: ['POST'])]
    public function confirmarDevolucion(int $id, int $devolucionId, Request $request, GestionarDevolucionService $gestionarDevolucion): Response
    {
        $this->validarCsrf($request);

        try {
            $gestionarDevolucion->confirmar($id, $devolucionId, (string) $request->request->get('referenciaSalida'));
            $this->addFlash('success', 'devolucion confirmada y cerrada');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('cuenta_show', ['id' => $id]);
    }

    private function validarCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('submit', $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('token csrf invalido');
        }
    }
}
