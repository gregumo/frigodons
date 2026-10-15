<?php

declare(strict_types=1);

namespace App\Controller;

use App\Altcha\Altcha;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Served as plain JSON, as expected by the `challengeurl` attribute of the ALTCHA widget.
 */
final readonly class AltchaChallengeController
{
    public function __construct(
        private Altcha $altcha,
    ) {}

    #[Route('/altcha-challenge', name: 'altcha_challenge', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $response = new JsonResponse($this->altcha->createChallenge());
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
