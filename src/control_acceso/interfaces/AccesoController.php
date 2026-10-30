<?php

namespace App\control_acceso\interfaces;

use App\control_acceso\application\EvaluarIngresoService;
use App\control_acceso\application\RestablecerAccesoService;
use App\control_acceso\application\SuspenderAccesoService;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccesoController extends AbstractController
{
    #[Route('/accesos', name: 'acceso_index', methods: ['GET'])]
    public function index(AccesoMiembroRepositoryInterface $accesos): Response
    {
        return $this->render('acceso/index.html.twig', [
            'accesos' => $accesos->todos(),
        ]);
    }

    #[Route('/accesos/{id}', name: 'acceso_show', methods: ['GET'])]
    public function show(int $id, AccesoMiembroRepositoryInterface $accesos): Response
    {
        $acceso = $accesos->obtener($id);
        if (null === $acceso) {
            throw $this->createNotFoundException('acceso no encontrado');
        }

        return $this->render('acceso/show.html.twig', [
            'acceso' => $acceso,
        ]);
    }

    #[Route('/accesos/{id}/suspender', name: 'acceso_suspender', methods: ['POST'])]
    public function suspender(int $id, Request $request, SuspenderAccesoService $suspenderAcceso): Response
    {
        $this->validarCsrf($request);

        $resultado = $suspenderAcceso->evaluarYSolicitar($id);
        if ($resultado['solicitada']) {
            $this->addFlash('success', 'suspension solicitada, falta confirmar el retiro de huella');
        } else {
            $this->addFlash('error', $resultado['motivo']);
        }

        return $this->redirectToRoute('acceso_show', ['id' => $id]);
    }

    #[Route('/accesos/{id}/operaciones/{operacionId}/confirmar-suspension', name: 'acceso_confirmar_suspension', methods: ['POST'])]
    public function confirmarSuspension(int $id, int $operacionId, Request $request, SuspenderAccesoService $suspenderAcceso): Response
    {
        $this->validarCsrf($request);

        try {
            $suspenderAcceso->confirmar($id, $operacionId);
            $this->addFlash('success', 'retiro de huella confirmado, acceso suspendido');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('acceso_show', ['id' => $id]);
    }

    #[Route('/accesos/{id}/restablecer', name: 'acceso_restablecer', methods: ['POST'])]
    public function restablecer(int $id, Request $request, RestablecerAccesoService $restablecerAcceso): Response
    {
        $this->validarCsrf($request);

        $resultado = $restablecerAcceso->evaluarYSolicitar($id, (string) $request->request->get('autorizadoPor', 'Recepcion'));
        if ($resultado['solicitada']) {
            $this->addFlash('success', 'restablecimiento solicitado, falta confirmar el alta de huella');
        } else {
            $this->addFlash('error', $resultado['motivo']);
        }

        return $this->redirectToRoute('acceso_show', ['id' => $id]);
    }

    #[Route('/accesos/{id}/operaciones/{operacionId}/confirmar-restablecimiento', name: 'acceso_confirmar_restablecimiento', methods: ['POST'])]
    public function confirmarRestablecimiento(int $id, int $operacionId, Request $request, RestablecerAccesoService $restablecerAcceso): Response
    {
        $this->validarCsrf($request);

        try {
            $restablecerAcceso->confirmar($id, $operacionId);
            $this->addFlash('success', 'alta de huella confirmada, acceso habilitado');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('acceso_show', ['id' => $id]);
    }

    #[Route('/accesos/{id}/evaluar-ingreso', name: 'acceso_evaluar_ingreso', methods: ['POST'])]
    public function evaluarIngreso(int $id, Request $request, EvaluarIngresoService $evaluarIngreso): Response
    {
        $this->validarCsrf($request);

        $resultado = $evaluarIngreso->evaluar($id);
        $this->addFlash($resultado['permitido'] ? 'success' : 'error', sprintf(
            'ingreso %s: %s',
            $resultado['permitido'] ? 'permitido' : 'denegado',
            $resultado['motivo']
        ));

        return $this->redirectToRoute('acceso_show', ['id' => $id]);
    }

    private function validarCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('submit', $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('token csrf invalido');
        }
    }
}
