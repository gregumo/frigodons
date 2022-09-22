<?php

namespace App\Entity;

use App\Repository\CleaningDateRepository;
use App\Repository\DateInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CleaningDateRepository::class)]
class CleaningDate implements DateInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $day = null;

    #[ORM\ManyToOne(inversedBy: 'cleaningDates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $cleaner = null;

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

    public function getCleaner(): ?User
    {
        return $this->cleaner;
    }

    public function setCleaner(?User $cleaner): self
    {
        $this->cleaner = $cleaner;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->getCleaner();
    }

    public function setUser(?User $user): self
    {
        return $this->setCleaner($user);
    }
}
