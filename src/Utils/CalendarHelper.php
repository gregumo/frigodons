<?php

namespace App\Utils;

use App\Entity\CleaningDate;
use App\Entity\SupervisingDate;
use Doctrine\Persistence\ManagerRegistry;
use Twig\Environment;

class CalendarHelper
{
    private Environment $twig;
    private ManagerRegistry $doctrine;
    private CalendarDaysInjector $injector;
    private ParticipationHelper $participationHelper;

    public function __construct(
        Environment $twig,
        ManagerRegistry      $doctrine,
        CalendarDaysInjector $injector,
        ParticipationHelper $participationHelper
    )
    {
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->injector = $injector;
        $this->participationHelper = $participationHelper;
    }

    public function prepareView($month, $year, $template): string
    {
        $currentDate = $month !== null && $year !== null ?
            \DateTime::createFromFormat('n-Y', sprintf('%s-%s', $month, $year)) :
            new \DateTime('first day of this month');

        $currentMonth = $currentDate->format('n');
        $currentYear = $currentDate->format('Y');

        $previousDate = clone $currentDate;
        $previousDate->modify('first day of -1 month');
        $previousMonth = $previousDate->format('n');
        $previousMonthYear = $previousDate->format('Y');

        $nextDate = clone $currentDate;
        $nextDate->modify('first day of +1 month');
        $nextMonth = $nextDate->format('n');
        $nextMonthYear = $nextDate->format('Y');

        $calendarDays = $this->getMonthViewDays($currentDate);
        $supervisingDates = $this->injector->inject(
            CalendarDaysInjector::SUPERVISING_CONTEXT,
            $calendarDays,
            $this->doctrine->getRepository(SupervisingDate::class)->findBetweenDates(reset($calendarDays), end($calendarDays))
        );
        $cleaningDates = $this->injector->inject(
            CalendarDaysInjector::CLEANING_CONTEXT,
            $calendarDays,
            $this->doctrine->getRepository(CleaningDate::class)->findBetweenDates(reset($calendarDays), end($calendarDays))
        );

        $participationContent = $this->participationHelper->render();

        $intl = \IntlDateFormatter::create('fr_FR',\IntlDateFormatter::FULL,\IntlDateFormatter::NONE, null,\IntlDateFormatter::GREGORIAN, 'MMMM Y');
        $frMonthDate = ucfirst($intl->format($currentDate));

        return $this->twig->render($template, compact(
            'participationContent','frMonthDate', 'calendarDays', 'supervisingDates', 'cleaningDates',
            'currentMonth', 'currentYear', 'previousMonth', 'previousMonthYear', 'nextMonth', 'nextMonthYear'
        ));
    }

    public function getMonthViewDays(\DateTime $currentDate): array
    {
        $clone = clone $currentDate;
        $monthFirstDayOfWeek = $clone->modify('first day of this month')->format('w');
        $clone = clone $currentDate;
        $monthLastDayOfWeek = $clone->modify('last day of this month')->format('w');

        $clone = clone $currentDate;
        $firstCalendarDay = $monthFirstDayOfWeek == 1 ? $clone->modify('first day of this month') : $clone->modify('last Monday of -1 month');
        $clone = clone $currentDate;
        $lastCalendarDay = $monthLastDayOfWeek == 0 ? $clone->modify('last day of this month') : $clone->modify('first Sunday of +1 month');

        $days = [];
        $day = $firstCalendarDay;
        while ($day->format('Y-m-d') <= $lastCalendarDay->format('Y-m-d')) {
            $days[] = $day->format('Y-m-d');
            $day = $day->modify('+1 day');
        }

        return $days;
    }
}
