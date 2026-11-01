<?php

namespace App\Controller;

use App\control_acceso\domain\AccesoMiembro;
use App\control_acceso\domain\AccesoMiembroRepositoryInterface;
use App\Entity\Cliente;
use App\Repository\ClienteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClienteController extends AbstractController
{
    #[Route('/clientes', name: 'cliente_index', methods: ['GET'])]
    public function index(ClienteRepository $clientes): Response
    {
        return $this->render('cliente/index.html.twig', [
            'clientes' => $clientes->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/clientes/nuevo', name: 'cliente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ClienteRepository $clientes, AccesoMiembroRepositoryInterface $accesos): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('submit', $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('token csrf invalido');
            }

            $cliente = new Cliente(
                (string) $request->request->get('nombre'),
                (string) $request->request->get('email'),
                (string) $request->request->get('plan'),
            );

            $em = $clientes->getEntityManager();
            $em->persist($cliente);
            $em->flush();

            $miembroId = $cliente->getId();
            if (null === $miembroId) {
                throw new \LogicException('el cliente debe persistirse antes de habilitar su acceso');
            }
            $accesos->guardar(AccesoMiembro::registrarInicial($miembroId));

            $this->addFlash('success', 'cliente registrado, ya tiene acceso habilitado');

            return $this->redirectToRoute('cliente_index');
        }

        return $this->render('cliente/new.html.twig');
    }
}
