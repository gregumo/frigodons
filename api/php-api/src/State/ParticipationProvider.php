<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Participation;
use App\Entity\User;
use App\Repository\CleaningDateRepository;
use App\Repository\SupervisingDateRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<Participation>
 */
final readonly class ParticipationProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private CleaningDateRepository $cleaningDateRepository,
        private SupervisingDateRepository $supervisingDateRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Participation
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('A user must be authenticated to get its participation.');
        }

        return new Participation(
            yearDoneCleaningCount: $this->cleaningDateRepository->countYearDoneDatesForUser($user),
            yearScheduledCleaningCount: $this->cleaningDateRepository->countYearScheduledDatesForUser($user),
            yearDoneSupervisingCount: $this->supervisingDateRepository->countYearDoneDatesForUser($user),
            yearScheduledSupervisingCount: $this->supervisingDateRepository->countYearScheduledDatesForUser($user),
        );
    }
}
