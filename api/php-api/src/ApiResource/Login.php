<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\LoginProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Volunteers log in with their email only: there is no password.
 */
#[ApiResource(operations: [
    new Post(uriTemplate: '/login', status: 200, output: Token::class, processor: LoginProcessor::class),
])]
final class Login
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Email(message: "L'adresse e-mail {{ value }} n'est pas une adresse valide.", normalizer: 'trim')]
    public string $email = '';
}
