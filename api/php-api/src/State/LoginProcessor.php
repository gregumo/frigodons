<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Login;
use App\ApiResource\Token;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<Login, Token>
 */
final readonly class LoginProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $tokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Token
    {
        $user = $this->userRepository->findOneByEmail($data->email);
        if (null === $user) {
            throw new UnauthorizedHttpException('Bearer', "Cette adresse e-mail n'est pas reconnue.");
        }

        return new Token($this->tokenManager->create($user));
    }
}
