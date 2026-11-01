<?php

namespace App\control_acceso\domain;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acceso_miembro')]
#[ORM\UniqueConstraint(name: 'UNIQ_C9EE68A97952B53A', columns: ['miembro_id'])]
class AccesoMiembro
{
    public const HABILITADO = 'habilitado';
    public const SUSPENSION_PENDIENTE = 'suspension_pendiente';
    public const SUSPENDIDO = 'suspendido';
    public const RESTABLECIMIENTO_PENDIENTE = 'restablecimiento_pendiente';

    public const PRESENTE = 'presente';
    public const AUSENTE = 'ausente';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'miembro_id')]
    private int $miembroId;

    #[ORM\Column(length: 30)]
    private string $estado = self::HABILITADO;

    #[ORM\Column(length: 20)]
    private string $observacion = self::PRESENTE;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    #[ORM\OneToMany(targetEntity: OperacionRegistroHuella::class, mappedBy: 'acceso', cascade: ['persist'], orphanRemoval: true)]
    private Collection $operaciones;

    private function __construct(int $miembroId)
    {
        if ($miembroId <= 0) {
            throw new \InvalidArgumentException('el miembro debe estar persistido');
        }
        $this->miembroId = $miembroId;
        $this->actualizadoEn = new \DateTimeImmutable();
        $this->operaciones = new ArrayCollection();
    }

    public static function registrarInicial(int $miembroId): self
    {
        return new self($miembroId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMiembroId(): int
    {
        return $this->miembroId;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getObservacion(): string
    {
        return $this->observacion;
    }

    public function getOperaciones(): Collection
    {
        return $this->operaciones;
    }

    public function tieneOperacionNoTerminal(): bool
    {
        foreach ($this->operaciones as $operacion) {
            if ($operacion->esNoTerminal()) {
                return true;
            }
        }

        return false;
    }

    public function buscarOperacion(int $operacionId): ?OperacionRegistroHuella
    {
        foreach ($this->operaciones as $operacion) {
            if ($operacion->getId() === $operacionId) {
                return $operacion;
            }
        }

        return null;
    }

    public function solicitarSuspension(string $motivo): OperacionRegistroHuella
    {
        if ($this->estado !== self::HABILITADO) {
            throw new \DomainException('solo se puede solicitar suspension desde habilitado');
        }
        if ($this->tieneOperacionNoTerminal()) {
            throw new \DomainException('ya existe una operacion de huella en curso');
        }

        $operacion = new OperacionRegistroHuella($this, OperacionRegistroHuella::RETIRO_IMPAGO, $motivo);
        $this->operaciones->add($operacion);
        $this->estado = self::SUSPENSION_PENDIENTE;

        return $operacion;
    }

    public function confirmarSuspension(int $operacionId, EvidenciaRegistroHuella $evidencia): void
    {
        if ($this->estado !== self::SUSPENSION_PENDIENTE) {
            throw new \DomainException('no hay una suspension pendiente que confirmar');
        }

        $operacion = $this->buscarOperacion($operacionId);
        if (null === $operacion || $operacion->getTipo() !== OperacionRegistroHuella::RETIRO_IMPAGO) {
            throw new \DomainException('la operacion no corresponde a esta suspension');
        }

        $operacion->confirmar($evidencia, new \DateTimeImmutable());
        $this->observacion = self::AUSENTE;
        $this->estado = self::SUSPENDIDO;
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    public function solicitarRestablecimiento(string $autorizadoPor): OperacionRegistroHuella
    {
        if ($this->estado !== self::SUSPENDIDO) {
            throw new \DomainException('solo se puede restablecer desde suspendido');
        }
        if ($this->tieneOperacionNoTerminal()) {
            throw new \DomainException('ya existe una operacion de huella en curso');
        }

        $operacion = new OperacionRegistroHuella($this, OperacionRegistroHuella::ALTA, 'autorizado por ' . $autorizadoPor);
        $this->operaciones->add($operacion);
        $this->estado = self::RESTABLECIMIENTO_PENDIENTE;

        return $operacion;
    }

    public function confirmarRestablecimiento(int $operacionId, EvidenciaRegistroHuella $evidencia): void
    {
        if ($this->estado !== self::RESTABLECIMIENTO_PENDIENTE) {
            throw new \DomainException('no hay un restablecimiento pendiente que confirmar');
        }

        $operacion = $this->buscarOperacion($operacionId);
        if (null === $operacion || $operacion->getTipo() !== OperacionRegistroHuella::ALTA) {
            throw new \DomainException('la operacion no corresponde a este restablecimiento');
        }

        $operacion->confirmar($evidencia, new \DateTimeImmutable());
        $this->observacion = self::PRESENTE;
        $this->estado = self::HABILITADO;
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
