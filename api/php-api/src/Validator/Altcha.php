<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * The value must be the payload of a solved ALTCHA challenge.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Altcha extends Constraint
{
    public string $message = 'La vérification anti-robot a échoué, veuillez réessayer.';
}
