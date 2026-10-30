<?php

namespace App\cobros\domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cobros_concepto_cobro')]
class ConceptoCobro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CuentaPorCobrar::class, inversedBy: 'conceptos')]
    #[ORM\JoinColumn(nullable: false)]
    private CuentaPorCobrar $cuenta;

    #[ORM\Column(length: 40)]
    private string $tipo;

    #[ORM\Column(length: 255)]
    private string $descripcion;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $cantidad;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $precio;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $descuento;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $neto;

    public function __construct(CuentaPorCobrar $cuenta, string $tipo, string $descripcion, float $cantidad, float $precio, float $descuento = 0.0)
    {
        if ($cantidad <= 0 || $precio < 0 || $descuento < 0) {
            throw new \InvalidArgumentException('cantidad, precio o descuento invalidos');
        }

        $this->cuenta = $cuenta;
        $this->tipo = $tipo;
        $this->descripcion = $descripcion;
        $this->cantidad = (string) $cantidad;
        $this->precio = (string) $precio;
        $this->descuento = (string) $descuento;

        $neto = round($cantidad * $precio - $descuento, 2);
        if ($neto <= 0) {
            throw new \InvalidArgumentException('el concepto no puede tener un neto cero o negativo');
        }
        $this->neto = (string) $neto;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getCantidad(): float
    {
        return (float) $this->cantidad;
    }

    public function getPrecio(): float
    {
        return (float) $this->precio;
    }

    public function getDescuento(): float
    {
        return (float) $this->descuento;
    }

    public function getNeto(): float
    {
        return (float) $this->neto;
    }
}
