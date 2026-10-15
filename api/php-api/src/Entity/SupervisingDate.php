<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\IriFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\SupervisingDateRepository;
use App\State\CalendarDateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SupervisingDateRepository::class)]
#[UniqueEntity(fields: ['day'], message: 'Cette date est déjà réservée.')]
#[ApiResource(
    operations: [
        new GetCollection(order: ['day' => 'ASC'], parameters: [
            'day' => new QueryParameter(filter: new DateFilter()),
            'supervisor' => new QueryParameter(filter: new IriFilter()),
        ]),
        new Get(),
        new Post(security: "is_granted('ROLE_MANAGER')", processor: CalendarDateProcessor::class),
        new Delete(
            security: 'object.getUser() == user and object.isCancellable()',
            securityMessage: 'Vous ne pouvez pas annuler cette date.',
        ),
    ],
    normalizationContext: ['groups' => ['date:read']],
    denormalizationContext: ['groups' => ['date:write']],
    security: "is_granted('ROLE_USER')",
)]
class SupervisingDate extends CalendarDate
{
    #[ORM\ManyToOne(inversedBy: 'supervisingDates')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['date:read'])]
    private ?User $supervisor = null;

    public function getSupervisor(): ?User
    {
        return $this->supervisor;
    }

    public function getUser(): ?User
    {
        return $this->supervisor;
    }

    public function setUser(User $user): static
    {
        $this->supervisor = $user;

        return $this;
    }
}
