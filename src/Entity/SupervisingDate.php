<?php

namespace App\Entity;

use App\Repository\DateInterface;
use App\Repository\SupervisingDateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupervisingDateRepository::class)]
class SupervisingDate implements DateInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $day = null;

    #[ORM\ManyToOne(inversedBy: 'supervisingDates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $supervisor = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?\DateTimeInterface
    {
        return $this->day;
    }

    public function setDay(\DateTimeInterface $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getSupervisor(): ?User
    {
        return $this->supervisor;
    }

    public function setSupervisor(?User $supervisor): self
    {
        $this->supervisor = $supervisor;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->getSupervisor();
    }

    public function setUser(?User $user): self
    {
        return $this->setSupervisor($user);
    }
}
