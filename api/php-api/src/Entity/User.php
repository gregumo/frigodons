<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Repository\UserRepository;
use App\State\MeProvider;
use App\State\UserDirectoryProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est déjà utilisée.')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_MANAGER')",
            normalizationContext: ['groups' => ['user:read', 'user:directory']],
            provider: UserDirectoryProvider::class,
        ),
        new Get(security: 'object == user'),
        new Get(
            uriTemplate: '/me',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['user:read'], 'item_uri_template' => '/users/{id}'],
            provider: MeProvider::class,
        ),
        new Patch(security: 'object == user'),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
class User implements UserInterface
{
    public const string ROLE_VOLUNTEER = 'ROLE_VOLUNTEER';
    public const string ROLE_MANAGER = 'ROLE_MANAGER';

    public const string MAN = 'm';
    public const string WOMAN = 'w';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'date:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email(message: "L'adresse e-mail {{ value }} n'est pas une adresse valide.")]
    #[Groups(['user:read', 'user:write', 'date:read'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write', 'date:read'])]
    private ?string $firstname = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write', 'date:read'])]
    private ?string $lastname = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^(?:[\s.-]*\d{2}){5}$/', message: 'Le numéro doit être au format 00 00 00 00 00.')]
    #[Groups(['user:read', 'user:write', 'date:read'])]
    private ?string $phone = null;

    #[ORM\Column(length: 1, nullable: true)]
    #[Groups(['user:read', 'date:read'])]
    private ?string $gender = self::WOMAN;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    /** @var Collection<int, CleaningDate> */
    #[ORM\OneToMany(targetEntity: CleaningDate::class, mappedBy: 'cleaner', orphanRemoval: true)]
    private Collection $cleaningDates;

    /** @var Collection<int, SupervisingDate> */
    #[ORM\OneToMany(targetEntity: SupervisingDate::class, mappedBy: 'supervisor', orphanRemoval: true)]
    private Collection $supervisingDates;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    private bool $callbackMailOptIn = false;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    private bool $missingVolunteerMailOptIn = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $hasBeenTrained = false;

    /**
     * Soft-deleted users are hidden by the `soft_delete` Doctrine filter.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * Number of cleaning dates booked this year, only computed for the directory.
     */
    #[ApiProperty(writable: false)]
    #[Groups(['user:directory'])]
    public ?int $yearCleaningCount = null;

    /**
     * Number of supervising dates booked this year, only computed for the directory.
     */
    #[ApiProperty(writable: false)]
    #[Groups(['user:directory'])]
    public ?int $yearSupervisingCount = null;

    public function __construct()
    {
        $this->cleaningDates = new ArrayCollection();
        $this->supervisingDates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * The id is used, rather than the email, so tokens remain valid when users change their email.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstname, $this->lastname);
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /**
     * The first role is the main role of the user (`ROLE_VOLUNTEER` or `ROLE_MANAGER`).
     *
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    #[Groups(['user:read'])]
    #[SerializedName('manager')]
    public function isManager(): bool
    {
        return \in_array(self::ROLE_MANAGER, $this->roles, true);
    }

    /**
     * @return Collection<int, CleaningDate>
     */
    public function getCleaningDates(): Collection
    {
        return $this->cleaningDates;
    }

    /**
     * @return Collection<int, SupervisingDate>
     */
    public function getSupervisingDates(): Collection
    {
        return $this->supervisingDates;
    }

    public function isCallbackMailOptIn(): bool
    {
        return $this->callbackMailOptIn;
    }

    public function setCallbackMailOptIn(bool $callbackMailOptIn): static
    {
        $this->callbackMailOptIn = $callbackMailOptIn;

        return $this;
    }

    public function isMissingVolunteerMailOptIn(): bool
    {
        return $this->missingVolunteerMailOptIn;
    }

    public function setMissingVolunteerMailOptIn(bool $missingVolunteerMailOptIn): static
    {
        $this->missingVolunteerMailOptIn = $missingVolunteerMailOptIn;

        return $this;
    }

    #[Groups(['user:read'])]
    public function getHasBeenTrained(): bool
    {
        return $this->hasBeenTrained;
    }

    public function setHasBeenTrained(bool $hasBeenTrained): static
    {
        $this->hasBeenTrained = $hasBeenTrained;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}
