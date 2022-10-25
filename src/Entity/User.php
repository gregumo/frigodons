<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const MAN = 'm';
    const WOMAN = 'w';

    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(
        message: 'L\'adresse e-mail {{ value }} n\'est pas une adresse valide.',
    )]
    private ?string $email = null;

    #[ORM\Column()]
    private ?string $password = null;

    #[ORM\Column()]
    #[Assert\NotBlank]
    private ?string $firstname = null;

    #[ORM\Column()]
    #[Assert\NotBlank]
    private ?string $lastname = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^(?:[\s.-]*\d{2}){5}$/',
        message: 'Le numéro doit être au format 00 00 00 00 00'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $gender = self::WOMAN;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\OneToMany(mappedBy: 'cleaner', targetEntity: CleaningDate::class, orphanRemoval: true)]
    private Collection $cleaningDates;

    #[ORM\OneToMany(mappedBy: 'supervisor', targetEntity: SupervisingDate::class, orphanRemoval: true)]
    private Collection $supervisingDates;

    #[ORM\Column(type: 'boolean', options: ['default' => '0'])]
    private bool $callbackMailOptIn = false;

    #[ORM\Column(type: 'boolean', options: ['default' => '0'])]
    private bool $missingVolunteerMailOptIn = false;

    #[ORM\Column(type: 'boolean', options: ['default' => '0'])]
    private bool $hasBeenTrained = false;

    public function __construct()
    {
        $this->cleaningDates = new ArrayCollection();
        $this->supervisingDates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getFullName(): ?string
    {
        return sprintf('%s %s', $this->firstname, $this->lastname);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function isCallbackMailOptIn(): bool
    {
        return $this->callbackMailOptIn;
    }

    public function setCallbackMailOptIn(bool $callbackMailOptIn): self
    {
        $this->callbackMailOptIn = $callbackMailOptIn;

        return $this;
    }

    public function isMissingVolunteerMailOptIn(): bool
    {
        return $this->missingVolunteerMailOptIn;
    }

    public function setMissingVolunteerMailOptIn(bool $missingVolunteerMailOptIn): self
    {
        $this->missingVolunteerMailOptIn = $missingVolunteerMailOptIn;

        return $this;
    }

    public function isHasBeenTrained(): bool
    {
        return $this->hasBeenTrained;
    }

    public function setHasBeenTrained(bool $hasBeenTrained): self
    {
        $this->hasBeenTrained = $hasBeenTrained;

        return $this;
    }

    public function isManager(): bool
    {
        return in_array('ROLE_MANAGER', $this->getRoles());
    }

    public function isVolunteer(): bool
    {
        return in_array('ROLE_VOLUNTEER', $this->getRoles());
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, CleaningDate>
     */
    public function getCleaningDates(): Collection
    {
        return $this->cleaningDates;
    }

    public function addCleaningDate(CleaningDate $cleaningDate): self
    {
        if (!$this->cleaningDates->contains($cleaningDate)) {
            $this->cleaningDates->add($cleaningDate);
            $cleaningDate->setCleaner($this);
        }

        return $this;
    }

    public function removeCleaningDate(CleaningDate $cleaningDate): self
    {
        if ($this->cleaningDates->removeElement($cleaningDate)) {
            // set the owning side to null (unless already changed)
            if ($cleaningDate->getCleaner() === $this) {
                $cleaningDate->setCleaner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SupervisingDate>
     */
    public function getSupervisingDates(): Collection
    {
        return $this->supervisingDates;
    }

    public function addSupervisingDate(SupervisingDate $supervisingDate): self
    {
        if (!$this->supervisingDates->contains($supervisingDate)) {
            $this->supervisingDates->add($supervisingDate);
            $supervisingDate->setSupervisor($this);
        }

        return $this;
    }

    public function removeSupervisingDate(SupervisingDate $supervisingDate): self
    {
        if ($this->supervisingDates->removeElement($supervisingDate)) {
            // set the owning side to null (unless already changed)
            if ($supervisingDate->getSupervisor() === $this) {
                $supervisingDate->setSupervisor(null);
            }
        }

        return $this;
    }
}
