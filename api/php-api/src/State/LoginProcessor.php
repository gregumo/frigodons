<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Login;
use App\ApiResource\Token;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * @implements ProcessorInterface<Login, Token>
 */
final readonly class LoginProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $tokenManager,
        private RequestStack $requestStack,
        private RateLimiterFactoryInterface $loginLimiter,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Token
    {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';
        $limit = $this->loginLimiter->create($ip)->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(
                $limit->getRetryAfter()->getTimestamp() - time(),
                'Trop de tentatives de connexion, veuillez réessayer dans une minute.',
            );
        }

        $user = $this->userRepository->findOneByEmail($data->email);
        if (null === $user) {
            throw new UnauthorizedHttpException('Bearer', "Cette adresse e-mail n'est pas reconnue.");
        }

        return new Token($this->tokenManager->create($user));
    }
}
