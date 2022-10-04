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
            ->environments('production')
        ;

        $schedule->addCommand('app:missing-volunteer-mail >> ~/frigodons_logs/missing_volunteer_mail.log 2>&1')
            ->sundays()
            ->at(5)
        ;
    }
}
