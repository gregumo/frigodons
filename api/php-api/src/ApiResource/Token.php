<?php

declare(strict_types=1);

namespace App\ApiResource;

/**
 * A JWT to send in the `Authorization: Bearer <token>` header of the following requests.
 */
final readonly class Token
{
    public function __construct(
        public string $token,
    ) {}
}
