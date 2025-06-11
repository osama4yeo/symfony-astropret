<?php

namespace App\Controller;

use App\Entity\Event; // <-- N'oublie pas d'importer ton entité Event !
use App\Service\GoogleCalendarService;
use App\Service\IcsImporterService;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    // =========================================================================
    //   MÉTHODE getEvents ENTIÈREMENT REVUE POUR LA SYNCHRONISATION
    // =================================e========================================
    #[Route('/api/calendar/events', name: 'calendar_events')]
    public function getEvents(EventRepository $eventRepository): JsonResponse
    {
        $data = [];
        $googleEventIdsFromLocalDb = []; // Pour stocker les UID des événements déjà en BDD

        // 1. On charge tous les événements depuis notre base de données locale.
        // C'est notre source de vérité pour les événements gérés par l'application.
        $localEvents = $eventRepository->findAll();
        foreach ($localEvents as $event) {
            $data[] = [
                'id' => 'db_' . $event->getId(), // L'ID préfixé est crucial pour le front-end.
                'title' => $event->getTitle(),
                'start' => $event->getStart()?->format('Y-m-d\TH:i:s'),
                'end' => $event->getEnd()?->format('Y-m-d\TH:i:s'),
                'allDay' => $event->isAllDay(),
                'description' => $event->getDescription(),
                // On donne une couleur différente selon la source pour une meilleure visibilité.
                'color' => match ($event->getSource()) {
                    'ics' => '#0dcaf0', // Cyan pour les imports ICS
                    'manual' => $event->getUid() ? '#28a745' : '#007bff', // Vert si synchro Google, Bleu sinon
                    default => '#6c757d', // Gris pour les autres cas
                },
            ];

            // Si l'événement local est lié à un événement Google, on stocke son UID.
            if ($event->getUid()) {
                $googleEventIdsFromLocalDb[] = $event->getUid();
            }
        }

        // 2. On récupère les événements de Google Calendar.
        $googleEventsFetched = $this->googleCalendarService->getEvents();

        // 3. On ajoute SEULEMENT les événements Google qui ne sont PAS déjà dans notre base de données.
        foreach ($googleEventsFetched as $gEvent) {
            // Si l'ID de l'événement Google n'est PAS dans notre liste d'UID, alors c'est un doublon.
            if (!in_array($gEvent['id'], $googleEventIdsFromLocalDb)) {
                $data[] = [
                    'id' => $gEvent['id'], // On utilise l'ID Google directement.
                    'title' => $gEvent['title'],
                    'start' => $gEvent['start'],
                    'end' => $gEvent['end'],
                    'allDay' => $gEvent['allDay'] ?? false,
                    'description' => $gEvent['description'] ?? null,
                    'color' => '#ffc107', // Jaune pour les événements qui ne sont QUE sur Google.
                    'editable' => false, // On peut les rendre non modifiables pour simplifier.
                ];
            }
        }

        return new JsonResponse($data);
    }

    // =========================================================================
    //   MÉTHODE createEvent REVUE POUR LA SYNCHRONISATION
    // =========================================================================
    #[Route('/api/calendar/create', name: 'calendar_create_event', methods: ['POST'])]
    public function createEvent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        // Crée une nouvelle instance de notre entité locale.
        $event = new Event();
        $event->setTitle($data['title']);
        $event->setDescription($data['description'] ?? null);
        $event->setStart(new \DateTime($data['start']));
        $event->setEnd(new \DateTime($data['end']));
        $event->setAllDay($data['allDay'] ?? false);
        $event->setSource('manual'); // Indique qu'il a été créé depuis notre interface.

        try {
            // 1. On tente de créer l'événement sur Google Calendar.
            $googleEventId = $this->googleCalendarService->createEvent($data);

            // 2. Si ça a marché, on lie notre événement local à l'événement Google via son UID.
            if ($googleEventId) {
                $event->setUid($googleEventId);
            }

            // 3. On sauvegarde notre événement en base de données.
            $em->persist($event);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Événement créé localement' . ($googleEventId ? ' et synchronisé avec Google Calendar.' : '.')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //   MÉTHODE updateEvent REVUE POUR LA SYNCHRONISATION
    // =========================================================================
    #[Route('/api/calendar/update', name: 'calendar_update_event', methods: ['POST'])]
    public function updateEvent(Request $request, EntityManagerInterface $em, EventRepository $eventRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);
        $eventId = $data['id'];

        // On ne met à jour que les événements qui viennent de notre base de données ('db_...').
        if (strpos($eventId, 'db_') !== 0) {
            return new JsonResponse(['success' => false, 'error' => 'Seuls les événements locaux peuvent être modifiés.'], 403);
        }

        $localId = (int) str_replace('db_', '', $eventId);
        $event = $eventRepository->find($localId);

        if (!$event) {
            return new JsonResponse(['success' => false, 'error' => 'Événement local non trouvé.'], 404);
        }

        try {
            // 1. On met à jour l'entité locale avec les nouvelles données.
            $event->setTitle($data['title']);
            $event->setDescription($data['description'] ?? null);
            $event->setStart(new \DateTime($data['start']));
            $event->setEnd(new \DateTime($data['end']));
            $event->setAllDay($data['allDay'] ?? false);
            $em->flush();

            // 2. Si cet événement est lié à Google (il a un UID)...
            if ($event->getUid()) {
                // ...on le met aussi à jour sur Google Calendar.
                $this->googleCalendarService->updateEvent($event->getUid(), $data);
                $message = 'Événement mis à jour localement et sur Google Calendar.';
            } else {
                // Optionnel : si on modifie un événement ICS, on pourrait vouloir le créer sur Google.
                // Pour l'instant, on se contente de la mise à jour locale.
                $message = 'Événement mis à jour localement.';
            }
            
            return new JsonResponse(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //   MÉTHODE deleteEvent REVUE POUR LA SYNCHRONISATION
    // =========================================================================
    #[Route('/api/calendar/delete', name: 'calendar_delete_event', methods: ['POST'])]
    public function deleteEvent(Request $request, EntityManagerInterface $em, EventRepository $eventRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);
        $eventId = $data['id'] ?? null;

        if (!$eventId) {
            return new JsonResponse(['success' => false, 'error' => 'ID d\'événement manquant.'], 400);
        }
        
        try {
            // On ne peut supprimer que les événements de notre base de données.
            if (strpos($eventId, 'db_') === 0) {
                $localId = (int) str_replace('db_', '', $eventId);
                $event = $eventRepo->find($localId);

                if (!$event) {
                    return new JsonResponse(['success' => false, 'error' => 'Événement local introuvable.'], 404);
                }

                // 1. Si l'événement est lié à Google, on le supprime d'abord de Google.
                if ($event->getUid()) {
                    $this->googleCalendarService->deleteEvent($event->getUid());
                }

                // 2. On supprime l'événement de notre base de données.
                $em->remove($event);
                $em->flush();

                return new JsonResponse(['success' => true, 'message' => 'Événement supprimé.']);

            } else {
                // Pour la cohérence, on peut aussi permettre de supprimer un événement "Google-only".
                $this->googleCalendarService->deleteEvent($eventId);
                return new JsonResponse(['success' => true, 'message' => 'Événement Google Calendar supprimé.']);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //   LES AUTRES MÉTHODES (IMPORT ICS, AUTH GOOGLE) RESTENT INCHANGÉES
    // =========================================================================

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
                $this->addFlash('danger', 'Erreur lors de l\'import : ' . $e->getMessage());
            }
    
            return $this->redirectToRoute('calendar_manage');
        }
    
        return $this->render('manage.html.twig');
    }

    #[Route('/google/auth/init', name: 'google_auth_init')]
    public function googleAuthInit(): Response
    {
        // ... (cette méthode reste identique)
        $client = new Client();
        $client->setAuthConfig($this->getParameter('kernel.project_dir') . '/config/google-credentials.json');
        $client->addScope('https://www.googleapis.com/auth/calendar');
        $client->setAccessType('offline');
        $client->setRedirectUri($this->generateUrl('google_auth_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL));
        $authUrl = $client->createAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/google/auth/callback', name: 'google_auth_callback')]
    public function googleAuthCallback(Request $request): Response
    {
        // ... (cette méthode reste identique)
        $code = $request->query->get('code');
        if (!$code) { return new Response('❌ Aucun code reçu depuis Google.'); }
        $client = new Client();
        $client->setAuthConfig($this->getParameter('kernel.project_dir') . '/config/google-credentials.json');
        $client->setRedirectUri($this->generateUrl('google_auth_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL));
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($accessToken['error'])) { return new Response('❌ Erreur lors de la récupération du token : ' . $accessToken['error']);}
        file_put_contents($this->getParameter('kernel.project_dir') . '/config/token.json', json_encode($accessToken));
        return new Response('✅ Connexion réussie ! Vous pouvez retourner à la page de gestion du calendrier.');
    }
}