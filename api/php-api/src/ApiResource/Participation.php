<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\ParticipationProvider;

/**
 * The participation of the current user this year.
 */
#[ApiResource(operations: [
    new Get(uriTemplate: '/participation', security: "is_granted('ROLE_USER')", provider: ParticipationProvider::class),
])]
final readonly class Participation
{
    public function __construct(
        public int $yearDoneCleaningCount,
        public int $yearScheduledCleaningCount,
        public int $yearDoneSupervisingCount,
        public int $yearScheduledSupervisingCount,
    ) {}
}
