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
            ->environments('prod')
        ;

        $schedule->addCommand('app:callback-mail')
            ->everyFiveMinutes()
            //->sundays()
            //->at(6)
        ;
    }
}
