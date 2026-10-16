<?php

declare(strict_types=1);

namespace App\Validator;

use App\Altcha\Altcha as AltchaService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AltchaValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AltchaService $altcha,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Altcha) {
            throw new UnexpectedTypeException($constraint, Altcha::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) || !$this->altcha->verify($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
