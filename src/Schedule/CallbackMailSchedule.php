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
            ->arguments('>>', '~/frigodons_logs/callback_mail.log', '2>&1')
            ->everyFiveMinutes()
            //->sundays()
            //->at(6)
        ;
    }
}
