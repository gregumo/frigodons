<?php

namespace App\Controller;

use App\DataTransformer\DatesCollectionTransformer;
use App\Entity\CleaningDate;
use App\Entity\SupervisingDate;
use App\Repository\DateInterface;
use App\Security\Voter\DayVoter;
use App\Utils\CalendarDaysInjector;
use App\Utils\CalendarHelper;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    #[Route('/calendrier/{month<\d+>}/{year<\d+>}', name: 'app_calendar')]
    public function index(CalendarHelper $calendarHelper, ?int $month = null, ?int $year = null): Response
    {
        return new Response($calendarHelper->prepareView($month, $year, 'calendar/index.html.twig'));
    }

    #[Route('/calendrier/add', name: 'app_calendar_add', options: ['expose' => true], methods: ['POST'])]
    public function addDate(Request $request, ManagerRegistry $doctrine, CalendarHelper $calendarHelper): JsonResponse
    {
        $em = $doctrine->getManager();

        $parameters = json_decode($request->getContent(), true);

        $context = $parameters['context'];
        $day = $parameters['day'];
        $month = $parameters['month'];
        $year = $parameters['year'];

        $this->denyAccessUnlessGranted(DayVoter::ADD, $context);

        /** @var DateInterface $date */
        $date = $context === CalendarDaysInjector::SUPERVISING_CONTEXT ? new SupervisingDate() : new CleaningDate();
        $date->setUser($this->getUser());
        $date->setDay(\DateTime::createFromFormat('Y-m-d', $day));
        $em->persist($date);
        $em->flush();

        return $this->json([
            'html' => $calendarHelper->prepareView($month, $year, 'calendar/_calendar.html.twig')
        ]);
    }

    #[Route('/calendrier/delete', name: 'app_calendar_delete', options: ['expose' => true], methods: ['DELETE'])]
    public function deleteDate(Request $request, ManagerRegistry $doctrine, CalendarHelper $calendarHelper): JsonResponse
    {
        $em = $doctrine->getManager();

        $parameters = json_decode($request->getContent(), true);

        $context = $parameters['context'];
        $day = $parameters['day'];
        $month = $parameters['month'];
        $year = $parameters['year'];

        $repo = $em->getRepository($context === CalendarDaysInjector::CLEANING_CONTEXT ? CleaningDate::class : SupervisingDate::class);

        $persistedDay = $repo->findOneByDay(\DateTime::createFromFormat('Y-m-d',$day));

        $this->denyAccessUnlessGranted(DayVoter::DELETE, $persistedDay);

        $em->remove($persistedDay);
        $em->flush();

        return $this->json([
            'html' => $calendarHelper->prepareView($month, $year, 'calendar/_calendar.html.twig')
        ]);
    }
}
