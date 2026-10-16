<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\CalendarDate;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Books a calendar date for the current user.
 *
 * @implements ProcessorInterface<CalendarDate, CalendarDate>
 */
final readonly class CalendarDateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<CalendarDate, CalendarDate> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CalendarDate {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('A user must be authenticated to book a date.');
        }

        $data->setUser($user);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
