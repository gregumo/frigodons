<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DateExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('nbYearDate', [$this, 'nbYearDate']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('frDate', [$this, 'frDate']),
        ];
    }

    public function nbYearDate(): int
    {
        if((new \DateTime())->format('Y') == 2022) {
            return 366 - date('z', strtotime('2022-10-22'))+1;
        }

        return date('z', strtotime('2022-12-31'))+1;
    }

    public function frDate($date)
    {
        $intl = \IntlDateFormatter::create('fr_FR',\IntlDateFormatter::FULL,\IntlDateFormatter::NONE, null,\IntlDateFormatter::GREGORIAN);

        return ucfirst($intl->format($date));
    }
}
