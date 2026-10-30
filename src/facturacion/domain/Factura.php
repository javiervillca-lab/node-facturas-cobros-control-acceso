<?php

namespace App\facturacion\domain;

use App\cobros\domain\CuentaPorCobrar;
use App\Entity\Cliente;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'facturacion_factura')]
#[ORM\UniqueConstraint(name: 'uniq_factura_cuenta', columns: ['cuenta_id'])]
class Factura
{
    public const PENDIENTE_DE_EMISION = 'pendiente_de_emision';
    public const EMITIDA = 'emitida';
    public const ENVIADA = 'enviada';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CuentaPorCobrar::class)]
    #[ORM\JoinColumn(name: 'cuenta_id', nullable: false)]
    private CuentaPorCobrar $cuenta;

    #[ORM\ManyToOne(targetEntity: Cliente::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Cliente $cliente;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total;

    #[ORM\Column(length: 3)]
    private string $moneda;

    #[ORM\Column(length: 20)]
    private string $estado = self::PENDIENTE_DE_EMISION;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $referenciaFiscal = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emitidaEn = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enviadaEn = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadaEn;

    #[ORM\OneToMany(targetEntity: LineaFactura::class, mappedBy: 'factura', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lineas;

    private function __construct(CuentaPorCobrar $cuenta)
    {
        $this->cuenta = $cuenta;
        $this->cliente = $cuenta->getCliente();
        $this->moneda = $cuenta->getMoneda();
        $this->creadaEn = new \DateTimeImmutable();
        $this->lineas = new ArrayCollection();
    }

    public static function prepararDesde(CuentaPorCobrar $cuenta): self
    {
        if ($cuenta->getEstado() !== CuentaPorCobrar::SALDADA) {
            throw new \DomainException('solo se puede preparar una factura desde una cuenta saldada');
        }

        $factura = new self($cuenta);

        $lineas = [];
        foreach ($cuenta->getConceptos() as $concepto) {
            $lineas[] = new LineaFactura($factura, $concepto->getDescripcion(), $concepto->getCantidad(), $concepto->getPrecio(), $concepto->getNeto());
        }
        $factura->lineas = new ArrayCollection($lineas);
        $factura->total = (string) $cuenta->getTotal();

        return $factura;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): string
    {
        return sprintf('FAC-%04d', $this->id ?? 0);
    }

    public function getCuenta(): CuentaPorCobrar
    {
        return $this->cuenta;
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
    }

    public function getTotal(): float
    {
        return (float) $this->total;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getReferenciaFiscal(): ?string
    {
        return $this->referenciaFiscal;
    }

    public function getEmitidaEn(): ?\DateTimeImmutable
    {
        return $this->emitidaEn;
    }

    public function getEnviadaEn(): ?\DateTimeImmutable
    {
        return $this->enviadaEn;
    }

    public function getLineas(): Collection
    {
        return $this->lineas;
    }

    public function confirmarEmision(string $referenciaFiscal, \DateTimeImmutable $ahora): void
    {
        if ($this->estado !== self::PENDIENTE_DE_EMISION) {
            return;
        }

        $this->referenciaFiscal = $referenciaFiscal;
        $this->estado = self::EMITIDA;
        $this->emitidaEn = $ahora;
    }

    public function confirmarEnvio(\DateTimeImmutable $ahora): void
    {
        if ($this->estado !== self::EMITIDA) {
            throw new \DomainException('solo una factura emitida puede marcarse como enviada');
        }

        $this->estado = self::ENVIADA;
        $this->enviadaEn = $ahora;
    }
}
