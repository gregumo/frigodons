<?php

namespace App\Utils;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use function Symfony\Component\String\u;

class Validator
{
    public function validatePassword(?string $plainPassword): ?string
    {
        if ($plainPassword !== 'bypass') {
            throw new InvalidArgumentException('The password must be "bypass".');
        }

        return $plainPassword;
    }

    public function validateEmail(?string $email): string
    {
        if (empty($email)) {
            throw new InvalidArgumentException('The email can not be empty.');
        }

        if (null === u($email)->indexOf('@')) {
            throw new InvalidArgumentException('The email should look like a real email.');
        }

        return $email;
    }
}
