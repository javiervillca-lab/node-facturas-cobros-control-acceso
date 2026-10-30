<?php

namespace App\cobros\domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cobros_aplicacion_pago')]
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

    public function __construct(CuentaPorCobrar $cuenta, string $referencia, string $proveedor, float $importeRecibido, float $importeAplicado, float $exceso)
    {
        $this->cuenta = $cuenta;
        $this->referencia = $referencia;
        $this->proveedor = $proveedor;
        $this->importeRecibido = (string) $importeRecibido;
        $this->importeAplicado = (string) $importeAplicado;
        $this->exceso = (string) $exceso;
        $this->verificadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferencia(): string
    {
        return $this->referencia;
    }

    public function getProveedor(): string
    {
        return $this->proveedor;
    }

    public function getImporteRecibido(): float
    {
        return (float) $this->importeRecibido;
    }

    public function getImporteAplicado(): float
    {
        return (float) $this->importeAplicado;
    }

    public function getExceso(): float
    {
        return (float) $this->exceso;
    }

    public function getVerificadoEn(): \DateTimeImmutable
    {
        return $this->verificadoEn;
    }
}
