<?php

namespace App\cobros\domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cobros_aplicacion_pago', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_pago_proveedor_referencia', columns: ['proveedor', 'referencia'])])]
class AplicacionPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CuentaPorCobrar::class, inversedBy: 'aplicaciones')]
    #[ORM\JoinColumn(nullable: false)]
    private CuentaPorCobrar $cuenta;

    #[ORM\Column(length: 80)]
    private string $referencia;

    #[ORM\Column(length: 80)]
    private string $proveedor;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $importeRecibido;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $importeAplicado;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $exceso;

    #[ORM\Column]
    private \DateTimeImmutable $verificadoEn;

    #[ORM\Column(length: 100)]
    private string $verificacionId;

    #[ORM\Column]
    private bool $simulada;

    public function __construct(CuentaPorCobrar $cuenta, VerificacionPago $verificacion, string $importeAplicado, string $exceso)
    {
        $this->cuenta = $cuenta;
        $this->referencia = $verificacion->referencia;
        $this->proveedor = $verificacion->proveedor;
        $this->importeRecibido = $verificacion->importe;
        $this->importeAplicado = $importeAplicado;
        $this->exceso = $exceso;
        $this->verificadoEn = new \DateTimeImmutable();
        $this->verificacionId = $verificacion->id;
        $this->simulada = $verificacion->simulada;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCuenta(): CuentaPorCobrar
    {
        return $this->cuenta;
    }

    public function getReferencia(): string
    {
        return $this->referencia;
    }

    public function getProveedor(): string
    {
        return $this->proveedor;
    }

    public function getImporteRecibido(): string
    {
        return $this->importeRecibido;
    }

    public function getImporteAplicado(): string
    {
        return $this->importeAplicado;
    }

    public function getExceso(): string
    {
        return $this->exceso;
    }

    public function getVerificadoEn(): \DateTimeImmutable
    {
        return $this->verificadoEn;
    }

    public function getVerificacionId(): string
    {
        return $this->verificacionId;
    }

    public function esSimulada(): bool
    {
        return $this->simulada;
    }
}
