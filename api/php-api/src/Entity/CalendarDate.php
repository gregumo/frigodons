<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A day of the calendar booked by a volunteer, either to clean the fridge or to supervise.
 */
#[ORM\MappedSuperclass]
abstract class CalendarDate
{
    /**
     * A booked date can only be cancelled if it is more than this number of days away.
     */
    public const int CANCELLATION_DELAY_IN_DAYS = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['date:read'])]
    protected ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual('today', message: 'Vous ne pouvez pas réserver une date passée.')]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[Groups(['date:read', 'date:write'])]
    protected ?\DateTimeImmutable $day = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?\DateTimeImmutable
    {
        return $this->day;
    }

    public function setDay(\DateTimeImmutable $day): static
    {
        $this->day = $day;

        return $this;
    }

    abstract public function getUser(): ?User;

    abstract public function setUser(User $user): static;

    #[Groups(['date:read'])]
    public function isCancellable(): bool
    {
        $limit = new \DateTimeImmutable(sprintf('today +%d days', self::CANCELLATION_DELAY_IN_DAYS));

        return null !== $this->day && $this->day > $limit;
    }
}
