<?php

namespace App\facturacion\domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'facturacion_linea_factura')]
class LineaFactura
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Factura::class, inversedBy: 'lineas')]
    #[ORM\JoinColumn(nullable: false)]
    private Factura $factura;

    #[ORM\Column(length: 255)]
    private string $descripcion;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $cantidad;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $precio;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $neto;

    public function __construct(Factura $factura, string $descripcion, float $cantidad, float $precio, float $neto)
    {
        $this->factura = $factura;
        $this->descripcion = $descripcion;
        $this->cantidad = (string) $cantidad;
        $this->precio = (string) $precio;
        $this->neto = (string) $neto;
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

    public function getNeto(): float
    {
        return (float) $this->neto;
    }
}
