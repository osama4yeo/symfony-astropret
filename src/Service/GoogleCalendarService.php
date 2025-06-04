<?php
// src/Service/GoogleCalendarService.php

namespace App\Service;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleServiceCalendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventDateTime as GoogleCalendarEventDateTime;
use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;

class GoogleCalendarService
{
    private GoogleClient $client;
    private GoogleServiceCalendar $calendarService;
    private string $calendarId = "astropret@gmail.com"; // Idéalement injecté ou configurable
    private string $tokenPath;
    private string $credentialsPath;
    private string $projectDir; // Utilisé pour construire les chemins
    private ?LoggerInterface $logger;

    // --- CONSTRUCTEUR MODIFIÉ POUR ACCEPTER LES INJECTIONS ---
    public function __construct(string $projectDir, ?LoggerInterface $logger = null)
    {
        $this->projectDir = $projectDir;
        $this->logger = $logger;

        // Construit les chemins en utilisant $projectDir
        $this->credentialsPath = $this->projectDir . '/config/google-credentials.json';
        $this->tokenPath = $this->projectDir . '/config/token.json';

        $caBundlePath = $this->projectDir . '/cacert.pem'; // Fichier cacert.pem à la racine du projet

        if (!file_exists($caBundlePath)) {
            $this->logError("Fichier cacert.pem local non trouvé à: " . $caBundlePath . ". Guzzle utilisera les certificats système si php.ini n'est pas configuré.");
            $httpClient = new GuzzleHttpClient(); // Client Guzzle par défaut si cacert.pem n'est pas là
        } else {
            $guzzleClientOptions = ['verify' => $caBundlePath];
            $httpClient = new GuzzleHttpClient($guzzleClientOptions);
            $this->logInfo("Client Guzzle configuré avec cacert.pem local: " . $caBundlePath);
        }

        $this->client = new GoogleClient();
        $this->client->setHttpClient($httpClient); // Utilise le client Guzzle configuré

        if (!file_exists($this->credentialsPath)) {
            $this->logError("Fichier de credentials Google API non trouvé: " . $this->credentialsPath);
            throw new \RuntimeException("Configuration Google API incomplète: fichier de credentials manquant à " . $this->credentialsPath);
        }
        $this->client->setAuthConfig($this->credentialsPath);
        $this->client->addScope(GoogleServiceCalendar::CALENDAR); // Utilise la classe namespacée
        $this->client->setAccessType('offline');
        // $this->client->setPrompt('select_account consent'); // Généralement pour le premier flux d'auth

        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            if ($accessToken) {
                $this->client->setAccessToken($accessToken);
            } else {
                $this->logWarning("Impossible de décoder le token depuis " . $this->tokenPath . ". Le fichier est peut-être corrompu ou vide.");
            }
        } else {
            $this->logWarning("Fichier token.json non trouvé à " . $this->tokenPath . ". Un nouveau flux d'autorisation est nécessaire.");
            // Ici, vous devriez normalement initier un flux OAuth2 si aucun token n'existe.
            // Pour une application serveur qui doit fonctionner en arrière-plan,
            // vous devez obtenir le token (et le refresh token) une fois manuellement ou via un script CLI.
        }

        if ($this->client->isAccessTokenExpired()) {
            $this->logInfo("Token d'accès Google expiré. Tentative de rafraîchissement.");
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                try {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newAccessToken = $this->client->getAccessToken();
                    if ($newAccessToken) {
                        if (false === file_put_contents($this->tokenPath, json_encode($newAccessToken))) {
                            $this->logError("Impossible d'écrire le nouveau token dans " . $this->tokenPath);
                        } else {
                            $this->logInfo("Token d'accès rafraîchi et sauvegardé dans " . $this->tokenPath);
                        }
                    } else {
                        $this->logError("fetchAccessTokenWithRefreshToken n'a pas retourné de nouveau token.");
                    }
                } catch (\Exception $e) {
                    $this->logError("Erreur lors du rafraîchissement du token d'accès Google: " . $e->getMessage());
                    // Si le refresh token est invalide, il faudra en générer un nouveau.
                }
            } else {
                $this->logWarning("Token expiré mais aucun refresh_token disponible pour le rafraîchissement dans " . $this->tokenPath);
            }
        }

        if (!$this->client->getAccessToken()) {
            $this->logError("Aucun token d'accès Google valide après initialisation/rafraîchissement. Les appels API échoueront.");
        }
        
        $this->calendarService = new GoogleServiceCalendar($this->client); // Utilise la classe namespacée
    }

    // ... (méthodes logInfo, logWarning, logError) ...
    private function logInfo(string $message): void { if ($this->logger) $this->logger->info('[GoogleCalendarService] ' . $message); else error_log('[INFO GCalService] ' . $message); }
    private function logWarning(string $message): void { if ($this->logger) $this->logger->warning('[GoogleCalendarService] ' . $message); else error_log('[WARN GCalService] ' . $message); }
    private function logError(string $message): void { if ($this->logger) $this->logger->error('[GoogleCalendarService] ' . $message); else error_log('[ERROR GCalService] ' . $message); }


    public function getEvents(): array
    {
        if (!$this->client->getAccessToken()) {
            $this->logError("getEvents: Tentative d'appel API sans token d'accès valide.");
            return [['title' => 'Erreur Authentification Google', 'start' => date('Y-m-d')]];
        }
        $eventsList = [];
        try {
            $optParams = ['maxResults' => 250, 'orderBy' => 'startTime', 'singleEvents' => true];
            $results = $this->calendarService->events->listEvents($this->calendarId, $optParams);
            foreach ($results->getItems() as $event) {
                $eventsList[] = [
                    'id' => $event->getId(),
                    'title' => $event->getSummary() ?? 'Sans titre',
                    'start' => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(),
                    'end' => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
                    'description' => $event->getDescription()
                ];
            }
        } catch (\Exception $e) {
            $this->logError("Erreur lors de la récupération des événements Google Calendar: " . $e->getMessage());
            return [['title' => 'Erreur API Google Calendar', 'start' => date('Y-m-d')]];
        }
        return $eventsList;
    }

    public function createEvent(array $data): ?string
    {
        if (!$this->client->getAccessToken()) { $this->logError("createEvent: No valid access token."); return null; }
        
        $isAllDay = $data['allDay'] ?? false; // Suppose que 'allDay' peut être passé dans $data
        $startDateTime = $data['start']; // ex: '2024-07-01T09:00:00'
        $endDateTime = $data['end'];   // ex: '2024-07-01T10:00:00'

        $eventData = [
            'summary' => $data['title'],
            'description' => $data['description'] ?? '',
        ];

        if ($isAllDay) {
            $eventData['start'] = ['date' => substr($startDateTime, 0, 10)]; // Prend YYYY-MM-DD
             // Pour les événements 'allDay', Google attend que la date de fin soit exclusive.
             // Si l'événement dure toute la journée du 1er juillet, la date de fin doit être le 2 juillet.
            $endDateObject = new \DateTime(substr($endDateTime, 0, 10));
            $endDateObject->modify('+1 day');
            $eventData['end'] = ['date' => $endDateObject->format('Y-m-d')];
        } else {
            $eventData['start'] = ['dateTime' => $startDateTime, 'timeZone' => $data['timeZone'] ?? 'Europe/Paris'];
            $eventData['end'] = ['dateTime' => $endDateTime, 'timeZone' => $data['timeZone'] ?? 'Europe/Paris'];
        }
        
        $event = new GoogleCalendarEvent($eventData);

        try {
            return $this->calendarService->events->insert($this->calendarId, $event)->getId();
        } catch (\Exception $e) { $this->logError("Error creating Google Calendar event: " . $e->getMessage()); return null; }
    }

    public function updateEvent(string $eventId, array $data): bool
    {
        if (!$this->client->getAccessToken()) { $this->logError("updateEvent: No valid access token."); return false; }
        try {
            $event = $this->calendarService->events->get($this->calendarId, $eventId);
            $event->setSummary($data['title']);
            if (isset($data['description'])) $event->setDescription($data['description']);

            // Gérer les événements "allDay" pour la mise à jour également
            $isAllDay = $data['allDay'] ?? false; // ou déterminer à partir de $event->getStart()->getDate() != null

            if ($isAllDay) {
                $start = new GoogleCalendarEventDateTime();
                $start->setDate(substr($data['start'], 0, 10));
                $event->setStart($start);

                $end = new GoogleCalendarEventDateTime();
                $endDateObject = new \DateTime(substr($data['end'], 0, 10));
                $endDateObject->modify('+1 day');
                $end->setDate($endDateObject->format('Y-m-d'));
                $event->setEnd($end);
            } else {
                $start = new GoogleCalendarEventDateTime(['dateTime' => $data['start'], 'timeZone' => $data['timeZone'] ?? 'Europe/Paris']);
                $event->setStart($start);
                $end = new GoogleCalendarEventDateTime(['dateTime' => $data['end'], 'timeZone' => $data['timeZone'] ?? 'Europe/Paris']);
                $event->setEnd($end);
            }
            
            $this->calendarService->events->update($this->calendarId, $eventId, $event);
            return true;
        } catch (\Exception $e) { $this->logError("Error updating Google Calendar event ($eventId): " . $e->getMessage()); return false; }
    }

    public function deleteEvent(string $eventId): bool
    {
        if (!$this->client->getAccessToken()) { $this->logError("deleteEvent: No valid access token."); return false; }
        try {
            $this->calendarService->events->delete($this->calendarId, $eventId);
            return true;
        } catch (\Exception $e) { $this->logError("Error deleting Google Calendar event ($eventId): " . $e->getMessage()); return false; }
    }
}