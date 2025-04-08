<?php
namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class CalendarController extends AbstractController
{
    private GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    #[Route('/calendar', name: 'calendar_view', methods: ['GET'])]
    public function calendarView(): Response
    {
        return $this->render('calendar.html.twig');
    }

    #[Route('/api/calendar', name: 'api_calendar_events', methods: ['GET'])]
    public function getCalendarEvents(): JsonResponse
    {
        $events = $this->googleCalendarService->getEvents();
        return new JsonResponse($events);
    }


}
