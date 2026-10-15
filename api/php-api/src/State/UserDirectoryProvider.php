<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Provides all users, sorted by name, with their number of booked dates of the current year.
 *
 * @implements ProviderInterface<User>
 */
final readonly class UserDirectoryProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * @return list<User>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $users = $this->userRepository->findWithYearDateCounts();

        // Sort with a French collator, so accents are ignored (eg. "Élodie" is sorted with "Elodie").
        $collator = new \Collator('fr_FR');
        usort(
            $users,
            static fn(User $a, User $b): int => (int) $collator->compare($a->getFullName(), $b->getFullName()),
        );

        return $users;
    }
}
