<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MailBatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A log of the recurring emails sent to volunteers.
 */
#[ORM\Entity(repositoryClass: MailBatchRepository::class)]
class MailBatch
{
    public const string TYPE_CALLBACK = 'callback';
    public const string TYPE_MISSING_VOLUNTEER = 'missing_volunteer';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $type;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $sendAt;

    /**
     * @param list<string> $recipients
     */
    public function __construct(
        string $type,
        #[ORM\Column]
        private array $recipients,
    ) {
        $this->type = $type;
        $this->sendAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSendAt(): \DateTimeImmutable
    {
        return $this->sendAt;
    }

    /**
     * @return list<string>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }
}
