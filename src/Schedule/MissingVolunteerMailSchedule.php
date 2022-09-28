<?php

namespace App\Schedule;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class MissingVolunteerMailSchedule implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule
            ->timezone('UTC')
            ->environments('prod')
        ;

        $schedule->addCommand('app:missing-volunteer-mail')
            ->sundays()
            ->at(5)
        ;
    }
}
