<?php

namespace App\Utils;

use App\Entity\CleaningDate;
use App\Entity\SupervisingDate;
use App\Entity\User;
use App\Repository\DateInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarDaysInjector
{
    public const SUPERVISING_CONTEXT ='supervising';
    public const CLEANING_CONTEXT ='cleaning';

    private ?UserInterface $currentUser;
    private TranslatorInterface $translator;

    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator)
    {
        $this->currentUser = $tokenStorage->getToken()->getUser();
        $this->translator = $translator;
    }

    /**
     * @param array $calendarDays
     * @param array|SupervisingDate[]|CleaningDate[] $dates
     * @return array
     */
    public function inject(string $context, array $calendarDays, array $dates): array
    {
        $bookedDays = [];
        $threeDaysAfterToday = (new \DateTime())->add(new \DateInterval('P3D'))->format('Y-m-d');

        if (null !== $dates) {
            /** @var DateInterface $date */
            foreach ($dates as $date) {
                $bookedDays[$date->getDay()->format('Y-m-d')] = $date;
            }
        }

        $injectedCalendar = [];
        foreach ($calendarDays as $day) {

            $route = $method = $user = null;
            $modal = true;

            $intl = \IntlDateFormatter::create('fr_FR',\IntlDateFormatter::FULL,\IntlDateFormatter::NONE, null,\IntlDateFormatter::GREGORIAN);
            $frDate = $intl->format(\DateTime::createFromFormat('Y-m-d', $day));

            /** @var User $user */
            if(array_key_exists($day, $bookedDays)) {

                /** @var DateInterface $date */
                $date = $bookedDays[$day];
                $user = $date->getUser();

                if($this->currentUser === $user) {
                    if($day < (new \DateTime())->format('Y-m-d')){
                        $modal = false;
                    }
                    if($day > $threeDaysAfterToday) {
                        $mode = 'delete';
                        $route = 'app_calendar_delete';
                        $method = 'DELETE';
                    } else {
                        $mode = 'unauthorized_delete';
                    }
                } elseif ($context === self::SUPERVISING_CONTEXT){
                    $mode = 'supervisor_contact';
                } else {
                    $mode = 'cleaner_contact';
                }
            } elseif ($context === self::SUPERVISING_CONTEXT && !$this->currentUser->isManager()){
                $modal = false;
                $mode = 'blank';
            } else {
                if($day < (new \DateTime())->format('Y-m-d')){
                    $modal = false;
                }
                $mode = 'add';
                $route = 'app_calendar_add';
                $method = 'POST';
            }

            $injectedCalendar[$day] = [
                'modal' => $modal,
                'mode' => $mode,
                'title' => $this->translator->trans("modal.$mode.title", [
                    'gender' => $user?->getGender(),
                    'context' => $context
                ]),
                'content' => $this->translator->trans("modal.$mode.content", [
                    'date' => $frDate,
                    'fullName' => $user?->getFullName(),
                    'phone' => $user?->getPhone(),
                    'email' => $user?->getEmail(),
                    'gender' => $user?->getGender()
                ]),
                'sendBtn' => $this->translator->trans("modal.$mode.sendBtn"),
                'closeBtn' => $this->translator->trans("modal.$mode.closeBtn"),
                'displaySendBtn' => in_array($mode, ['add', 'delete']),
                'route' => $route,
                'method' => $method,
                'user' => $user,
            ];
        }

        return $injectedCalendar;
    }
}
