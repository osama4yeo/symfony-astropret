<?php
namespace App\Controller;

// On utilise un service que tu as créé pour se connecter à Google Calendar
use App\Service\GoogleCalendarService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class CalendarController extends AbstractController
{
    // Ce contrôleur a besoin du service GoogleCalendarService
    private GoogleCalendarService $googleCalendarService;

    // Symfony injecte automatiquement le service grâce au constructeur
    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    // 🟦 Route 1 : Affiche la page HTML avec le calendrier
    #[Route('/calendar', name: 'calendar_view', methods: ['GET'])]
    public function calendarView(): Response
    {
        // Affiche le fichier Twig qui contient le HTML du calendrier
        return $this->render('calendar.html.twig');
    }

    // 🟨 Route 2 : Envoie les événements au format JSON pour FullCalendar
    #[Route('/api/calendar', name: 'api_calendar_events', methods: ['GET'])]
    public function getCalendarEvents(): JsonResponse
    {
        // Appelle ton service pour récupérer les événements depuis Google Calendar
        $events = $this->googleCalendarService->getEvents();

        // Retourne les événements au format JSON pour FullCalendar
        return new JsonResponse($events);
    }
}
