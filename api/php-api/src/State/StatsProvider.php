<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Stats;
use App\Repository\UserRepository;

/**
 * @implements ProviderInterface<Stats>
 */
final readonly class StatsProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Stats
    {
        return new Stats(
            volunteerCount: $this->userRepository->countAll(),
            managerCount: $this->userRepository->countManagers(),
        );
    }
}
