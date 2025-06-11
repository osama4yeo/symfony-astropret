<?php

namespace App\Controller;

// --- Imports des classes nécessaires ---
use App\Entity\Event; // L'entité Doctrine qui représente un événement dans notre BDD.
use App\Service\GoogleCalendarService; // Le service pour communiquer avec Google Calendar.
use App\Service\IcsImporterService; // Le service pour importer les fichiers .ics.
use App\Repository\EventRepository; // Le service pour récupérer les événements de notre BDD.
use Doctrine\ORM\EntityManagerInterface; // Le service pour sauvegarder les changements en BDD.
use Google\Client; // Utilisé spécifiquement pour le flux d'authentification initial.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse; // Pour créer des réponses au format JSON pour les API.
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Le contrôleur principal pour toutes les fonctionnalités liées au calendrier.
 * Il gère l'affichage, la gestion, l'importation et la synchronisation des événements.
 */
class CalendarController extends AbstractController
{
    private GoogleCalendarService $googleCalendarService; // Stocke le service Google Calendar.

    /**
     * Le constructeur injecte le GoogleCalendarService pour qu'il soit disponible
     * dans toutes les méthodes du contrôleur.
     */
    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Affiche la page principale du calendrier (la vue publique).
     */
    #[Route('/calendar', name: 'calendar_view')]
    public function index(): Response
    {
        return $this->render('calendar.html.twig');
    }

    /**
     * L'API qui fournit tous les événements à FullCalendar.
     * C'est le cœur de la logique de synchronisation. Elle fusionne les événements
     * de la base de données locale et de Google Calendar, en évitant les doublons.
     */
    #[Route('/api/calendar/events', name: 'calendar_events')]
    public function getEvents(EventRepository $eventRepository): JsonResponse
    {
        $data = []; // Le tableau final qui contiendra tous les événements à afficher.
        $googleEventIdsFromLocalDb = []; // Un tableau pour garder en mémoire les UID des événements Google déjà synchronisés.

        // --- ÉTAPE 1 : Charger les événements de notre base de données locale ---
        $localEvents = $eventRepository->findAll();
        foreach ($localEvents as $event) {
            $data[] = [
                // L'ID est préfixé par 'db_' pour que le JavaScript puisse facilement
                // différencier un événement local d'un événement qui n'est que sur Google.
                'id'          => 'db_' . $event->getId(),
                'title'       => $event->getTitle(),
                'start'       => $event->getStart()?->format('Y-m-d\TH:i:s'), // Format ISO 8601 attendu par FullCalendar.
                'end'         => $event->getEnd()?->format('Y-m-d\TH:i:s'),   // L'opérateur '?->' évite une erreur si la date est null.
                'allDay'      => $event->isAllDay(),
                'description' => $event->getDescription(),
                // On assigne une couleur différente par source pour une meilleure lisibilité.
                'color' => match ($event->getSource()) {
                    'ics'    => '#0dcaf0', // Cyan pour les imports ICS.
                    'manual' => $event->getUid() ? '#28a745' : '#007bff', // Vert si synchro Google, Bleu si purement local.
                    default  => '#6c757d', // Gris pour les autres cas.
                },
            ];

            // Si l'événement local a un UID, cela signifie qu'il est synchronisé avec Google.
            // On stocke cet UID pour éviter de l'afficher une deuxième fois.
            if ($event->getUid()) {
                $googleEventIdsFromLocalDb[] = $event->getUid();
            }
        }

        // --- ÉTAPE 2 : Récupérer les événements depuis Google Calendar ---
        $googleEventsFetched = $this->googleCalendarService->getEvents();

        // --- ÉTAPE 3 : Fusionner les événements en évitant les doublons ---
        foreach ($googleEventsFetched as $gEvent) {
            // On vérifie si l'ID de l'événement Google (qui est son UID) est déjà dans notre liste
            // des événements synchronisés. Si c'est le cas, on l'ignore.
            if (!in_array($gEvent['id'], $googleEventIdsFromLocalDb)) {
                // S'il n'y est pas, c'est un événement qui existe sur Google Calendar mais pas encore dans notre BDD.
                $data[] = [
                    'id'          => $gEvent['id'], // On utilise l'ID Google directement (pas de préfixe).
                    'title'       => $gEvent['title'],
                    'start'       => $gEvent['start'],
                    'end'         => $gEvent['end'],
                    'allDay'      => $gEvent['allDay'] ?? false,
                    'description' => $gEvent['description'] ?? null,
                    'color'       => '#ffc107', // Jaune pour les événements "Google-only".
                    'editable'    => false, // On les rend non modifiables car ils ne sont pas dans notre BDD.
                ];
            }
        }

        // On retourne la liste complète des événements au format JSON.
        return new JsonResponse($data);
    }

    /**
     * API pour créer un nouvel événement.
     * Crée l'événement dans notre BDD et tente de le synchroniser avec Google Calendar.
     */
    #[Route('/api/calendar/create', name: 'calendar_create_event', methods: ['POST'])]
    public function createEvent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // Seuls les admins peuvent créer des événements.
        $data = json_decode($request->getContent(), true); // On récupère les données envoyées en JSON par le front-end.

        // On crée une nouvelle entité Event et on la remplit avec les données.
        $event = new Event();
        $event->setTitle($data['title']);
        $event->setDescription($data['description'] ?? null);
        $event->setStart(new \DateTime($data['start']));
        $event->setEnd(new \DateTime($data['end']));
        $event->setAllDay($data['allDay'] ?? false);
        $event->setSource('manual'); // On marque l'événement comme étant créé manuellement.

        try {
            // 1. On tente de créer l'événement sur Google Calendar.
            $googleEventId = $this->googleCalendarService->createEvent($data);

            // 2. Si la création sur Google a réussi, on stocke l'ID Google (UID) dans notre entité.
            // C'est ce qui fait le lien entre les deux systèmes.
            if ($googleEventId) {
                $event->setUid($googleEventId);
            }

            // 3. On sauvegarde notre nouvel événement dans notre base de données.
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

    /**
     * API pour mettre à jour un événement existant.
     * Met à jour l'événement local et, s'il est lié, le met aussi à jour sur Google Calendar.
     */
    #[Route('/api/calendar/update', name: 'calendar_update_event', methods: ['POST'])]
    public function updateEvent(Request $request, EntityManagerInterface $em, EventRepository $eventRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);
        $eventId = $data['id'];

        // On ne peut modifier que les événements qui sont dans notre BDD (ceux avec le préfixe 'db_').
        if (strpos($eventId, 'db_') !== 0) {
            return new JsonResponse(['success' => false, 'error' => 'Seuls les événements locaux peuvent être modifiés.'], 403);
        }

        // On extrait l'ID numérique de la chaîne 'db_123'.
        $localId = (int) str_replace('db_', '', $eventId);
        $event = $eventRepository->find($localId);

        if (!$event) {
            return new JsonResponse(['success' => false, 'error' => 'Événement local non trouvé.'], 404);
        }

        try {
            // 1. On met à jour les données de notre entité locale.
            $event->setTitle($data['title']);
            $event->setDescription($data['description'] ?? null);
            $event->setStart(new \DateTime($data['start']));
            $event->setEnd(new \DateTime($data['end']));
            $event->setAllDay($data['allDay'] ?? false);
            $em->flush(); // On sauvegarde les changements en BDD.

            // 2. Si l'événement a un UID, cela veut dire qu'il est lié à Google.
            if ($event->getUid()) {
                // On propage la mise à jour sur Google Calendar.
                $this->googleCalendarService->updateEvent($event->getUid(), $data);
                $message = 'Événement mis à jour localement et sur Google Calendar.';
            } else {
                $message = 'Événement mis à jour localement.';
            }
            
            return new JsonResponse(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API pour supprimer un événement.
     * Gère la suppression dans notre BDD et sur Google Calendar si nécessaire.
     */
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
            // CAS 1 : C'est un événement de notre base de données.
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

                // 2. Puis on le supprime de notre base de données.
                $em->remove($event);
                $em->flush();

                return new JsonResponse(['success' => true, 'message' => 'Événement supprimé.']);

            } else { // CAS 2 : C'est un événement "Google-only".
                // L'ID n'a pas de préfixe, c'est donc directement l'ID Google.
                $this->googleCalendarService->deleteEvent($eventId);
                return new JsonResponse(['success' => true, 'message' => 'Événement Google Calendar supprimé.']);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Affiche et traite la page de gestion (import ICS).
     */
    #[Route('/calendar/manage', name: 'calendar_manage', methods: ['GET', 'POST'])]
    public function manage(Request $request, IcsImporterService $icsImporter): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        // Si le formulaire d'import a été soumis...
        if ($request->isMethod('POST') && $request->request->get('ics_url')) {
            $icsUrl = $request->request->get('ics_url');
    
            try {
                // ...on appelle le service d'importation.
                $importResult = $icsImporter->importFromUrl($icsUrl);
                // On affiche un message flash de succès ou d'erreur en fonction du résultat.
                if (isset($importResult['error'])) {
                    $this->addFlash('danger', $importResult['error']);
                } else {
                    $this->addFlash('success', $importResult['added'] . ' événement(s) importé(s), ' . $importResult['skipped'] . ' ignoré(s).');
                }
                
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l\'import : ' . $e->getMessage());
            }
    
            // On redirige pour éviter la re-soumission du formulaire au rafraîchissement.
            return $this->redirectToRoute('calendar_manage');
        }
    
        // Si la requête est en GET, on affiche simplement la page de gestion.
        return $this->render('manage.html.twig');
    }

    /**
     * Redirige l'utilisateur vers la page d'autorisation de Google.
     * C'est la première étape du flux OAuth2, à n'exécuter qu'une seule fois.
     */
    #[Route('/google/auth/init', name: 'google_auth_init')]
    public function googleAuthInit(): Response
    {
        $client = new Client();
        $client->setAuthConfig($this->getParameter('kernel.project_dir') . '/config/google-credentials.json');
        $client->addScope('https://www.googleapis.com/auth/calendar');
        $client->setAccessType('offline');
        $client->setRedirectUri($this->generateUrl('google_auth_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL));
        $authUrl = $client->createAuthUrl();
        return $this->redirect($authUrl);
    }

    /**
     * La page de retour (callback) après que l'utilisateur a autorisé l'application sur Google.
     * Google redirige ici avec un code d'autorisation.
     */
    #[Route('/google/auth/callback', name: 'google_auth_callback')]
    public function googleAuthCallback(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) { return new Response('❌ Aucun code reçu depuis Google.'); }
        
        $client = new Client();
        $client->setAuthConfig($this->getParameter('kernel.project_dir') . '/config/google-credentials.json');
        $client->setRedirectUri($this->generateUrl('google_auth_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL));
        
        // On échange le code d'autorisation contre un token d'accès (et un refresh token).
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($accessToken['error'])) { return new Response('❌ Erreur lors de la récupération du token : ' . $accessToken['error']);}
        
        // On sauvegarde le token dans un fichier pour une utilisation future.
        file_put_contents($this->getParameter('kernel.project_dir') . '/config/token.json', json_encode($accessToken));
        
        return new Response('✅ Connexion réussie ! Vous pouvez retourner à la page de gestion du calendrier.');
    }
}