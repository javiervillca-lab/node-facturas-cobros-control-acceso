<?php

namespace App\control_acceso\domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acceso_aviso_previo')]
class AvisoPrevio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccesoMiembro::class)]
    #[ORM\JoinColumn(nullable: false)]
    private AccesoMiembro $acceso;

    #[ORM\Column]
    private int $pagadorId;

    /** @var list<int> */
    #[ORM\Column(type: 'json')]
    private array $obligacionIds;

    #[ORM\Column(length: 120)]
    private string $referencia;

    #[ORM\Column]
    private \DateTimeImmutable $enviadoEn;

    #[ORM\Column]
    private bool $simulado;

    /**
     * @param list<int> $obligacionIds
     */
    public function __construct(AccesoMiembro $acceso, int $pagadorId, array $obligacionIds, ResultadoAviso $resultado)
    {
        sort($obligacionIds);
        $this->acceso = $acceso;
        $this->pagadorId = $pagadorId;
        $this->obligacionIds = array_values(array_unique($obligacionIds));
        $this->referencia = $resultado->referencia;
        $this->enviadoEn = $resultado->enviadoEn;
        $this->simulado = $resultado->simulado;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnviadoEn(): \DateTimeImmutable
    {
        return $this->enviadoEn;
    }

    public function getReferencia(): string
    {
        return $this->referencia;
    }

    public function esSimulado(): bool
    {
        return $this->simulado;
    }

    /**
     * @param list<int> $obligacionIds
     */
    public function correspondeA(int $pagadorId, array $obligacionIds): bool
    {
        sort($obligacionIds);

        return $this->pagadorId === $pagadorId && $this->obligacionIds === array_values(array_unique($obligacionIds));
    }
}
