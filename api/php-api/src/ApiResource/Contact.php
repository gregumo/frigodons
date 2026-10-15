<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ContactProcessor;
use App\Validator\Altcha;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A message sent to La Cordée with the contact form.
 */
#[ApiResource(operations: [
    new Post(uriTemplate: '/contact', status: 202, output: false, processor: ContactProcessor::class),
])]
final class Contact
{
    #[Assert\NotBlank(normalizer: 'trim')]
    public string $firstname = '';

    #[Assert\NotBlank(normalizer: 'trim')]
    public string $lastname = '';

    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Email(message: "L'adresse e-mail {{ value }} n'est pas une adresse valide.", normalizer: 'trim')]
    public string $email = '';

    #[Assert\Regex(pattern: '/^(?:[\s.-]*\d{2}){5}$/', message: 'Le numéro doit être au format 00 00 00 00 00.')]
    public ?string $phone = null;

    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 5000)]
    public string $message = '';

    /**
     * Honeypot: a field hidden to visitors, so only bots fill it in. The message is then silently dropped.
     */
    public ?string $website = null;

    /**
     * The payload of the solved ALTCHA challenge (see `GET /altcha-challenge`).
     */
    #[Assert\NotBlank(message: 'Veuillez valider la vérification anti-robot.')]
    #[Altcha]
    public string $altcha = '';
}
