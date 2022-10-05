<?php

namespace App\Schedule;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class CallbackMailSchedule implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule
            ->timezone('UTC')
            ->environments('production')
        ;

        $schedule->addCommand('app:callback-mail')
            ->emailOnFailure('gregoire.humeau@gmail.com')
            ->sundays()
            ->at(6)
        ;
    }
}
