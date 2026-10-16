<?php

declare(strict_types=1);

namespace App\Altcha;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Proof-of-work challenges for the ALTCHA widget, which protects the contact form from bots.
 *
 * @see https://altcha.org/docs/server-integration
 */
final readonly class Altcha
{
    private const string ALGORITHM = 'SHA-256';
    private const int MAX_NUMBER = 100_000;
    private const int EXPIRATION_IN_SECONDS = 600;

    public function __construct(
        #[\SensitiveParameter]
        private string $altchaHmacKey,
        private CacheInterface $cache,
    ) {}

    /**
     * @return array{algorithm: string, challenge: string, maxnumber: int, salt: string, signature: string}
     */
    public function createChallenge(): array
    {
        $salt = sprintf('%s?expires=%d', bin2hex(random_bytes(12)), time() + self::EXPIRATION_IN_SECONDS);
        $challenge = hash('sha256', $salt . random_int(0, self::MAX_NUMBER));

        return [
            'algorithm' => self::ALGORITHM,
            'challenge' => $challenge,
            'maxnumber' => self::MAX_NUMBER,
            'salt' => $salt,
            'signature' => $this->sign($challenge),
        ];
    }

    /**
     * Verifies the payload sent by the widget: a base64 encoded JSON of the solved challenge.
     * A challenge can only be used once.
     */
    public function verify(string $payload): bool
    {
        $decoded = base64_decode($payload, true);
        /** @var mixed $solution */
        $solution = false === $decoded ? null : json_decode($decoded, true);
        if (
            !\is_array($solution)
            || self::ALGORITHM !== ($solution['algorithm'] ?? null)
            || !\is_string($solution['challenge'] ?? null)
            || !\is_int($solution['number'] ?? null)
            || !\is_string($solution['salt'] ?? null)
            || !\is_string($solution['signature'] ?? null)
        ) {
            return false;
        }

        $params = [];
        parse_str((string) parse_url($solution['salt'], PHP_URL_QUERY), $params);
        $expires = (int) ($params['expires'] ?? 0);

        if (
            $expires < time()
            || !hash_equals($solution['challenge'], hash('sha256', $solution['salt'] . $solution['number']))
            || !hash_equals($this->sign($solution['challenge']), $solution['signature'])
        ) {
            return false;
        }

        // The cache item is only created on the first use of the challenge.
        $firstUse = false;
        $this->cache->get('altcha_' . $solution['challenge'], static function (ItemInterface $item) use (
            $expires,
            &$firstUse,
        ): bool {
            $item->expiresAt(new \DateTimeImmutable('@' . $expires));
            $firstUse = true;

            return true;
        });

        return $firstUse;
    }

    private function sign(string $challenge): string
    {
        if ('' === $this->altchaHmacKey) {
            throw new \LogicException('The ALTCHA_HMAC_KEY environment variable is not set.');
        }

        return hash_hmac('sha256', $challenge, $this->altchaHmacKey);
    }
}
