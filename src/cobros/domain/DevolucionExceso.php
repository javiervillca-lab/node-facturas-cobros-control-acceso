<?php

namespace App\cobros\domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cobros_devolucion_exceso')]
class DevolucionExceso
{
    public const PENDIENTE = 'pendiente';
    public const APROBADA = 'aprobada';
    public const DEVUELTA = 'devuelta';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CuentaPorCobrar::class, inversedBy: 'devoluciones')]
    #[ORM\JoinColumn(nullable: false)]
    private CuentaPorCobrar $cuenta;

    #[ORM\ManyToOne(targetEntity: AplicacionPago::class)]
    #[ORM\JoinColumn(nullable: false)]
    private AplicacionPago $movimientoOrigen;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monto;

    #[ORM\Column(length: 20)]
    private string $estado = self::PENDIENTE;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $autorizadoPor = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $referenciaSalida = null;

    #[ORM\Column]
    private bool $simulada = false;

    public function __construct(CuentaPorCobrar $cuenta, AplicacionPago $movimientoOrigen, string $monto)
    {
        $this->cuenta = $cuenta;
        $this->movimientoOrigen = $movimientoOrigen;
        $this->monto = Dinero::desdeCentavos(Dinero::aCentavos($monto));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonto(): string
    {
        return $this->monto;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getAutorizadoPor(): ?string
    {
        return $this->autorizadoPor;
    }

    public function getReferenciaSalida(): ?string
    {
        return $this->referenciaSalida;
    }

    public function aprobar(string $autorizadoPor): void
    {
        if ($this->estado !== self::PENDIENTE) {
            throw new \DomainException('solo una devolucion pendiente puede aprobarse');
        }
        $this->estado = self::APROBADA;
        $this->autorizadoPor = $autorizadoPor;
    }

    public function confirmar(ResultadoDevolucion $resultado): void
    {
        if ($this->estado === self::DEVUELTA) {
            if ($this->referenciaSalida !== $resultado->referencia) {
                throw new \DomainException('la devolución ya fue confirmada con otra referencia');
            }
            return;
        }
        if ($this->estado !== self::APROBADA) {
            throw new \DomainException('la devolucion debe estar aprobada antes de confirmarse');
        }
        $this->estado = self::DEVUELTA;
        $this->referenciaSalida = $resultado->referencia;
        $this->simulada = $resultado->simulada;
    }

    public function esSimulada(): bool
    {
        return $this->simulada;
    }
}
