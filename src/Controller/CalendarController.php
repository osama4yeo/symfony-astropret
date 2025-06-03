<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Service\IcsImporterService;
use App\Repository\EventRepository;

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
    public function getEvents(
        EventRepository $eventRepository,
        GoogleCalendarService $googleCalendarService
    ): JsonResponse {
        $localEvents = $eventRepository->findAll();
        $googleEvents = $googleCalendarService->getEvents();
    
        $data = [];
    
        // Ajouter les événements importés (en base)
        foreach ($localEvents as $event) {
            $data[] = [
                'id' => 'db_' . $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getStart()?->format('Y-m-d\TH:i:s'),
                'end' => $event->getEnd()?->format('Y-m-d\TH:i:s'),
                'allDay' => $event->isAllDay(),
                'description' => $event->getDescription(),
                'color' => $event->getSource() === 'ics' ? '#0dcaf0' : null,
            ];
        }
    
        // Ajouter les événements Google
        foreach ($googleEvents as $event) {
            $data[] = $event;
        }
    
        return new JsonResponse($data);
    }
    

    #[Route('/calendar/manage', name: 'calendar_manage', methods: ['GET', 'POST'])]
    public function manage(Request $request, IcsImporterService $icsImporter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        if ($request->isMethod('POST') && $request->request->get('ics_url')) {
            $icsUrl = $request->request->get('ics_url');
    
            try {
                $importResult = $icsImporter->importFromUrl($icsUrl);
                if (isset($importResult['error'])) {
                    $this->addFlash('danger', $importResult['error']);
                } else {
                    $this->addFlash('success', $importResult['added'] . ' événement(s) importé(s), ' . $importResult['skipped'] . ' ignoré(s).');
                }
                
            } catch (\Exception $e) {
                $this->addFlash('import_result', json_encode([
                    'error' => 'Erreur lors de l\'import : ' . $e->getMessage(),
                ]));
            }
    
            // 🔁 redirection pour éviter le renvoi du POST
            return $this->redirectToRoute('calendar_manage');
        }
    
        return $this->render('manage.html.twig');
    }
    
    
    #[Route('/api/calendar/create', name: 'calendar_create_event', methods: ['POST'])]
    public function createEvent(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        $data = json_decode($request->getContent(), true);
    
        // 🔧 Ajout important pour gérer les événements "allDay"
        $data['allDay'] = $data['allDay'] ?? false;
    
        try {
            // Appel du service pour créer dans Google Calendar
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
    
    
    #[Route('/api/calendar/update', name: 'calendar_update_event', methods: ['POST'])]
    public function updateEvent(Request $request, GoogleCalendarService $googleCalendarService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        try {
            $googleCalendarService->updateEvent($data['id'], $data);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/calendar/delete', name: 'calendar_delete_event', methods: ['POST'])]
    public function deleteEvent(Request $request, GoogleCalendarService $googleCalendar): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $eventId = $data['id'] ?? null;
    
        if (!$eventId) {
            return new JsonResponse(['success' => false, 'error' => 'ID manquant'], 400);
        }
    
        try {
            $googleCalendar->deleteEvent($eventId);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }    

}