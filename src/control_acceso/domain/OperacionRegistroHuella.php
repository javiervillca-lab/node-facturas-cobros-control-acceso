<?php

namespace App\control_acceso\domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acceso_operacion_registro_huella')]
class OperacionRegistroHuella
{
    public const RETIRO_IMPAGO = 'retiro_impago';
    public const ALTA = 'alta';

    public const SOLICITADA = 'solicitada';
    public const CONFIRMADA = 'confirmada';
    public const CANCELADA = 'cancelada';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccesoMiembro::class, inversedBy: 'operaciones')]
    #[ORM\JoinColumn(nullable: false)]
    private AccesoMiembro $acceso;

    #[ORM\Column(length: 30)]
    private string $tipo;

    #[ORM\Column(length: 20)]
    private string $estado = self::SOLICITADA;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motivo = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadaEn;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resueltaEn = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $evidenciaId = null;

    #[ORM\Column]
    private bool $simulada = false;

    public function __construct(AccesoMiembro $acceso, string $tipo, ?string $motivo = null)
    {
        $this->acceso = $acceso;
        $this->tipo = $tipo;
        $this->motivo = $motivo;
        $this->creadaEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function getCreadaEn(): \DateTimeImmutable
    {
        return $this->creadaEn;
    }

    public function getEvidenciaId(): ?string
    {
        return $this->evidenciaId;
    }

    public function esSimulada(): bool
    {
        return $this->simulada;
    }

    public function esNoTerminal(): bool
    {
        return $this->estado === self::SOLICITADA;
    }

    public function confirmar(EvidenciaRegistroHuella $evidencia, \DateTimeImmutable $ahora): void
    {
        if ($this->estado === self::CONFIRMADA) {
            if ($this->evidenciaId === $evidencia->id) {
                return;
            }
            throw new \DomainException('la operación ya fue confirmada con otra evidencia');
        }
        if ($this->estado !== self::SOLICITADA) {
            throw new \DomainException('solo se puede confirmar una operación solicitada');
        }

        $this->estado = self::CONFIRMADA;
        $this->resueltaEn = $ahora;
        $this->evidenciaId = $evidencia->id;
        $this->simulada = $evidencia->simulada;
    }
}
