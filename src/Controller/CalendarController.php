<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CalendarController extends AbstractController
{
    private GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    #[Route('/calendar', name: 'calendar_view')]
    public function index(): Response
    {
        return $this->render('calendar.html.twig');
    }

    #[Route('/api/calendar/events', name: 'calendar_events')]
    public function getEvents(): JsonResponse
    {
        $events = $this->googleCalendarService->getEvents();
        return new JsonResponse($events);
    }

    #[Route('/calendar/manage', name: 'calendar_manage')]
    public function manage(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('manage.html.twig');
    }
    
    #[Route('/api/calendar/create', name: 'calendar_create_event', methods: ['POST'])]
    public function createEvent(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        $data = json_decode($request->getContent(), true);
    
        try {
            // Appel réel du service
            $googleEventId = $this->googleCalendarService->createEvent($data);
    
            return new JsonResponse([
                'success' => true,
                'googleEventId' => $googleEventId
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}
