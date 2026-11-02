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

    public function __construct(CuentaPorCobrar $cuenta, string $tipo, string $descripcion, string|int $cantidad, string|int $precio, string|int $descuento = '0')
    {
        $cantidadCentavos = Dinero::aCentavos($cantidad);
        $precioCentavos = Dinero::aCentavos($precio);
        $descuentoCentavos = Dinero::aCentavos($descuento);
        if ($cantidadCentavos <= 0 || $precioCentavos < 0 || $descuentoCentavos < 0) {
            throw new \InvalidArgumentException('cantidad, precio o descuento invalidos');
        }

        $this->cuenta = $cuenta;
        $this->tipo = $tipo;
        $this->descripcion = $descripcion;
        $this->cantidad = Dinero::desdeCentavos($cantidadCentavos);
        $this->precio = Dinero::desdeCentavos($precioCentavos);
        $this->descuento = Dinero::desdeCentavos($descuentoCentavos);

        $netoCentavos = Dinero::aCentavos(Dinero::multiplicar($cantidad, $precio)) - $descuentoCentavos;
        if ($netoCentavos <= 0) {
            throw new \InvalidArgumentException('el concepto no puede tener un neto cero o negativo');
        }
        $this->neto = Dinero::desdeCentavos($netoCentavos);
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

    public function getCantidad(): string
    {
        return $this->cantidad;
    }

    public function getPrecio(): string
    {
        return $this->precio;
    }

    public function getDescuento(): string
    {
        return $this->descuento;
    }

    public function getNeto(): string
    {
        return $this->neto;
    }
}
