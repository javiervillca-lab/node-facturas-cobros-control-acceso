<?php

namespace App\Controller;

use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\CuentaPorCobrarRepositoryInterface;
use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\facturacion\domain\Factura;
use App\facturacion\domain\FacturaRepositoryInterface;
use App\Repository\ClienteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        ClienteRepository $clientes,
        CuentaPorCobrarRepositoryInterface $cuentas,
        FacturaRepositoryInterface $facturas,
        AccesoMiembroRepositoryInterface $accesos,
    ): Response {
        $todasCuentas = $cuentas->todas();
        $todosAccesos = $accesos->todos();

        $pendientes = array_filter($todasCuentas, fn (CuentaPorCobrar $c) => $c->getEstado() === CuentaPorCobrar::PENDIENTE);
        $saldadas = array_filter($todasCuentas, fn (CuentaPorCobrar $c) => $c->getEstado() === CuentaPorCobrar::SALDADA);
        $suspendidos = array_filter($todosAccesos, fn (AccesoMiembro $a) => $a->getEstado() === AccesoMiembro::SUSPENDIDO);
        $facturasEmitidas = array_filter($facturas->todas(), fn (Factura $f) => $f->getEstado() !== Factura::PENDIENTE_DE_EMISION);

        return $this->render('home/index.html.twig', [
            'totalClientes' => count($clientes->findAll()),
            'cuentasPendientes' => count($pendientes),
            'cuentasSaldadas' => count($saldadas),
            'facturasEmitidas' => count($facturasEmitidas),
            'accesosSuspendidos' => count($suspendidos),
            'totalAccesos' => count($todosAccesos),
        ]);
    }
}
