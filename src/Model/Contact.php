<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Contact
{
    #[Assert\NotBlank]
    private string $firstname;

    #[Assert\NotBlank]
    private string $lastname;

    #[Assert\Regex(
        pattern: '/^(?:[\s.-]*\d{2}){5}$/',
        message: 'Le numéro doit être au format 00 00 00 00 00'
    )]
    private ?string $phone = null;

    private ?string $email = null;

    #[Assert\Email(
        message: 'L\'adresse e-mail {{ value }} n\'est pas une adresse valide.',
    )]
    private string $emailAddress;

    #[Assert\NotBlank]
    private string $message;

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
