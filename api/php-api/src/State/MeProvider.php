<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Provides the current user.
 *
 * @implements ProviderInterface<User>
 */
final readonly class MeProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('A user must be authenticated.');
        }

        return $user;
    }
}
