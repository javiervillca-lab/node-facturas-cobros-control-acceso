<?php

namespace App\facturacion\domain;

use App\cobros\domain\CuentaPorCobrar;
use App\cobros\domain\Dinero;
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

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $constanciaEnvio = null;

    #[ORM\Column]
    private bool $emisionSimulada = false;

    #[ORM\Column]
    private bool $envioSimulado = false;

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

    public function getTotal(): string
    {
        return $this->total;
    }

    public function getMoneda(): string
    {
        return $this->moneda;
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

    public function getConstanciaEnvio(): ?string
    {
        return $this->constanciaEnvio;
    }

    public function esEmisionSimulada(): bool
    {
        return $this->emisionSimulada;
    }

    public function esEnvioSimulado(): bool
    {
        return $this->envioSimulado;
    }

    public function getLineas(): Collection
    {
        return $this->lineas;
    }

    public function confirmarEmision(ResultadoEmisionFiscal $resultado, \DateTimeImmutable $ahora): void
    {
        if ($resultado->moneda !== $this->moneda || Dinero::aCentavos($resultado->total) !== Dinero::aCentavos($this->total)) {
            throw new \DomainException('el resultado fiscal no coincide con el total y moneda de la factura');
        }
        if ($this->estado === self::EMITIDA || $this->estado === self::ENVIADA) {
            if ($this->referenciaFiscal === $resultado->referenciaFiscal) {
                return;
            }
            throw new \DomainException('la factura ya fue emitida con otra referencia fiscal');
        }
        if ($this->estado !== self::PENDIENTE_DE_EMISION) {
            throw new \DomainException('la factura no está disponible para emisión');
        }

        $this->referenciaFiscal = $resultado->referenciaFiscal;
        $this->emisionSimulada = $resultado->simulada;
        $this->estado = self::EMITIDA;
        $this->emitidaEn = $ahora;
    }

    public function confirmarEnvio(ResultadoEnvioFactura $resultado, \DateTimeImmutable $ahora): void
    {
        if ($this->estado === self::ENVIADA) {
            if ($this->constanciaEnvio === $resultado->constancia) {
                return;
            }
            throw new \DomainException('la factura ya fue enviada con otra constancia');
        }
        if ($this->estado !== self::EMITIDA) {
            throw new \DomainException('solo una factura emitida puede marcarse como enviada');
        }

        $this->estado = self::ENVIADA;
        $this->enviadaEn = $ahora;
        $this->constanciaEnvio = $resultado->constancia;
        $this->envioSimulado = $resultado->simulado;
    }
}
