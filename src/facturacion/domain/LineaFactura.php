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

    public function __construct(Factura $factura, string $descripcion, string $cantidad, string $precio, string $neto)
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

    public function getCantidad(): string
    {
        return $this->cantidad;
    }

    public function getPrecio(): string
    {
        return $this->precio;
    }

    public function getNeto(): string
    {
        return $this->neto;
    }
}
