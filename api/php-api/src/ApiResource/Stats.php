<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\StatsProvider;

/**
 * Public figures about the volunteers community.
 */
#[ApiResource(operations: [
    new Get(uriTemplate: '/stats', provider: StatsProvider::class),
])]
final readonly class Stats
{
    public function __construct(
        public int $volunteerCount,
        public int $managerCount,
    ) {}
}
