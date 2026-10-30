<?php

namespace App\cobros\domain;

use App\Entity\Cliente;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cobros_cuenta_por_cobrar')]
class CuentaPorCobrar
{
    public const PENDIENTE = 'pendiente';
    public const SALDADA = 'saldada';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cliente::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Cliente $cliente;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $periodo = null;

    #[ORM\Column(length: 3)]
    private string $moneda = 'BOB';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $saldo;

    #[ORM\Column(length: 20)]
    private string $estado = self::PENDIENTE;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $vencimiento = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadaEn;

    #[ORM\OneToMany(targetEntity: ConceptoCobro::class, mappedBy: 'cuenta', cascade: ['persist'], orphanRemoval: true)]
    private Collection $conceptos;

    #[ORM\OneToMany(targetEntity: AplicacionPago::class, mappedBy: 'cuenta', cascade: ['persist'], orphanRemoval: true)]
    private Collection $aplicaciones;

    #[ORM\OneToMany(targetEntity: DevolucionExceso::class, mappedBy: 'cuenta', cascade: ['persist'], orphanRemoval: true)]
    private Collection $devoluciones;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    private function __construct(Cliente $cliente, ?string $periodo, string $moneda, ?\DateTimeImmutable $vencimiento)
    {
        $this->cliente = $cliente;
        $this->periodo = $periodo;
        $this->moneda = $moneda;
        $this->vencimiento = $vencimiento;
        $this->creadaEn = new \DateTimeImmutable();
        $this->conceptos = new ArrayCollection();
        $this->aplicaciones = new ArrayCollection();
        $this->devoluciones = new ArrayCollection();
        $this->total = '0.00';
        $this->saldo = '0.00';
    }

    /**
     * @param array<int, array{tipo: string, descripcion: string, cantidad: float, precio: float, descuento?: float}> $conceptosData
     */
    public static function crear(Cliente $cliente, ?string $periodo, string $moneda, array $conceptosData, ?\DateTimeImmutable $vencimiento = null): self
    {
        if (count($conceptosData) === 0) {
            throw new \DomainException('una cuenta no puede crearse sin conceptos');
        }

        $cuenta = new self($cliente, $periodo, $moneda, $vencimiento);

        $conceptos = [];
        foreach ($conceptosData as $dato) {
            $conceptos[] = new ConceptoCobro(
                $cuenta,
                $dato['tipo'],
                $dato['descripcion'],
                (float) $dato['cantidad'],
                (float) $dato['precio'],
                (float) ($dato['descuento'] ?? 0)
            );
        }

        $cuenta->conceptos = new ArrayCollection($conceptos);
        $cuenta->total = (string) CalculoDeCobro::totalDe($conceptos);
        $cuenta->saldo = $cuenta->total;

        return $cuenta;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): string
    {
        return sprintf('CPC-%04d', $this->id ?? 0);
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
    }

    public function getMoneda(): string
    {
        return $this->moneda;
    }

    public function getTotal(): float
    {
        return (float) $this->total;
    }

    public function getSaldo(): float
    {
        return (float) $this->saldo;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getVencimiento(): ?\DateTimeImmutable
    {
        return $this->vencimiento;
    }

    public function getConceptos(): Collection
    {
        return $this->conceptos;
    }

    public function getAplicaciones(): Collection
    {
        return $this->aplicaciones;
    }

    public function getDevoluciones(): Collection
    {
        return $this->devoluciones;
    }

    public function buscarAplicacionPorReferencia(string $referencia): ?AplicacionPago
    {
        foreach ($this->aplicaciones as $aplicacion) {
            if ($aplicacion->getReferencia() === $referencia) {
                return $aplicacion;
            }
        }

        return null;
    }

    public function buscarDevolucion(int $devolucionId): ?DevolucionExceso
    {
        foreach ($this->devoluciones as $devolucion) {
            if ($devolucion->getId() === $devolucionId) {
                return $devolucion;
            }
        }

        return null;
    }

    public function registrarPagoConfirmado(string $referencia, string $proveedor, float $importeRecibido): AplicacionPago
    {
        $existente = $this->buscarAplicacionPorReferencia($referencia);
        if (null !== $existente) {
            return $existente;
        }

        if ($importeRecibido <= 0) {
            throw new \DomainException('el importe recibido debe ser mayor a cero');
        }

        $saldoActual = $this->getSaldo();
        $importeAplicado = min($importeRecibido, $saldoActual);
        $exceso = round($importeRecibido - $importeAplicado, 2);

        $aplicacion = new AplicacionPago($this, $referencia, $proveedor, $importeRecibido, $importeAplicado, $exceso);
        $this->aplicaciones->add($aplicacion);

        $this->saldo = (string) round($saldoActual - $importeAplicado, 2);
        if ((float) $this->saldo <= 0) {
            $this->saldo = '0.00';
            $this->estado = self::SALDADA;
        }

        if ($exceso > 0) {
            $devolucion = new DevolucionExceso($this, $aplicacion, $exceso);
            $this->devoluciones->add($devolucion);
        }

        return $aplicacion;
    }
}
