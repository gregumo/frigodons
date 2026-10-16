<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Recurring tasks, run by the `worker` container (`messenger:consume scheduler_default`).
 */
#[AsSchedule]
final readonly class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getSchedule(): SymfonySchedule
    {
        $timezone = new \DateTimeZone('Europe/Paris');

        return new SymfonySchedule()
            // A task missed while the worker was down is run when it restarts.
            // Commands themselves make sure the emails are not sent twice.
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(
                // Sundays at 6am
                RecurringMessage::cron('0 6 * * 0', new RunCommandMessage('app:missing-volunteer-mail'), $timezone),
                // Sundays at 7am
                RecurringMessage::cron('0 7 * * 0', new RunCommandMessage('app:callback-mail'), $timezone),
            );
    }
}
