<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ColorExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('color_ratio', [$this, 'colorRatio']),
        ];
    }

    public function colorRatio($ratio)
    {
        if ($ratio < 34) {
            return 'text-red-700 bg-red-100';
        } else if ($ratio < 67) {
            return 'text-orange-700 bg-orange-100';
        } else {
            return 'text-green-700 bg-green-100';
        }
    }
}
