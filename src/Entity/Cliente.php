<?php

namespace App\Entity;

use App\Repository\ClienteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClienteRepository::class)]
#[ORM\Table(name: 'cliente')]
class Cliente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $nombre;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 60)]
    private string $plan;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $nombre, string $email, string $plan)
    {
        $this->nombre = $nombre;
        $this->email = $email;
        $this->plan = $plan;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
