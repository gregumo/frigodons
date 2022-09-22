<?php

namespace App\Security\Voter;

use App\Entity\CleaningDate;
use App\Entity\SupervisingDate;
use App\Entity\User;
use App\Repository\DateInterface;
use App\Utils\CalendarDaysInjector;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class DayVoter extends Voter
{
    public const ADD = 'DAY_ADD';
    public const DELETE = 'DAY_DELETE';

    protected function supports(string $attribute, $context): bool
    {
        $attributeTest = in_array($attribute, [self::ADD, self::DELETE]);
        $contextTest = in_array($context, [CalendarDaysInjector::SUPERVISING_CONTEXT, CalendarDaysInjector::CLEANING_CONTEXT]);
        $classTest = $context instanceof CleaningDate || $context instanceof SupervisingDate;

        return $attributeTest && ($contextTest || $classTest);
    }

    protected function voteOnAttribute(string $attribute, $context, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::ADD:
                if($context === CalendarDaysInjector::SUPERVISING_CONTEXT && !$user->isManager()){
                    return false;
                } else {
                    return true;
                }
            case self::DELETE:
                /** @var DateInterface $attribute */
                if($context && $context->getUser() == $user){
                    return true;
                } else {
                    return false;
                }
        }
    }
}
