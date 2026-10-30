<?php

namespace App\facturacion\interfaces;

use App\facturacion\application\EmitirFacturaService;
use App\facturacion\application\EnviarFacturaService;
use App\facturacion\domain\FacturaRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FacturaController extends AbstractController
{
    #[Route('/facturas', name: 'factura_index', methods: ['GET'])]
    public function index(FacturaRepositoryInterface $facturas): Response
    {
        return $this->render('factura/index.html.twig', [
            'facturas' => $facturas->todas(),
        ]);
    }

    #[Route('/facturas/preparar/{cuentaId}', name: 'factura_preparar', methods: ['POST'])]
    public function preparar(int $cuentaId, Request $request, EmitirFacturaService $emitirFactura): Response
    {
        $this->validarCsrf($request);

        try {
            $factura = $emitirFactura->preparar($cuentaId);
            $this->addFlash('success', sprintf('factura %s preparada, lista para emitir', $factura->getCodigo()));

            return $this->redirectToRoute('factura_show', ['id' => $factura->getId()]);
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('cuenta_show', ['id' => $cuentaId]);
        }
    }

    #[Route('/facturas/{id}', name: 'factura_show', methods: ['GET'])]
    public function show(int $id, FacturaRepositoryInterface $facturas): Response
    {
        $factura = $facturas->obtener($id);
        if (null === $factura) {
            throw $this->createNotFoundException('factura no encontrada');
        }

        return $this->render('factura/show.html.twig', [
            'factura' => $factura,
        ]);
    }

    #[Route('/facturas/{id}/emitir', name: 'factura_emitir', methods: ['POST'])]
    public function emitir(int $id, Request $request, EmitirFacturaService $emitirFactura): Response
    {
        $this->validarCsrf($request);

        $factura = $emitirFactura->confirmarEmision($id);
        $this->addFlash('success', sprintf('factura emitida con referencia %s', $factura->getReferenciaFiscal()));

        return $this->redirectToRoute('factura_show', ['id' => $id]);
    }

    #[Route('/facturas/{id}/enviar', name: 'factura_enviar', methods: ['POST'])]
    public function enviar(int $id, Request $request, EnviarFacturaService $enviarFactura): Response
    {
        $this->validarCsrf($request);

        try {
            $enviarFactura->ejecutar($id);
            $this->addFlash('success', 'factura marcada como enviada al cliente');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('factura_show', ['id' => $id]);
    }

    private function validarCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('submit', $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('token csrf invalido');
        }
    }
}
