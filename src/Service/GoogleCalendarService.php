<?php
// src/Service/GoogleCalendarService.php

namespace App\Service;

// --- Imports des classes nécessaires ---
// Classes de la bibliothèque Google API Client pour PHP
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleServiceCalendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventDateTime as GoogleCalendarEventDateTime;

// Client HTTP utilisé par la bibliothèque Google pour faire des requêtes
use GuzzleHttp\Client as GuzzleHttpClient;
// Interface de logging de Symfony, pour enregistrer des messages d'erreur ou d'information
use Psr\Log\LoggerInterface;

/**
 * Service pour interagir avec l'API Google Calendar.
 * Gère l'authentification, la récupération, la création, la mise à jour et la suppression d'événements.
 * Ce service est conçu pour être injecté dans les contrôleurs ou autres services de l'application.
 */
class GoogleCalendarService
{
    // --- Propriétés de la classe ---
    private GoogleClient $client; // L'objet client principal de Google. Gère l'authentification.
    private GoogleServiceCalendar $calendarService; // Le service spécifique pour l'API Calendar.
    private string $calendarId = "astropret@gmail.com"; // L'ID de l'agenda à utiliser.
    private string $tokenPath; // Chemin vers le fichier 'token.json' qui stocke le token d'accès.
    private string $credentialsPath; // Chemin vers le fichier 'google-credentials.json' qui contient les secrets de l'API.
    private string $projectDir; // Le répertoire racine du projet Symfony.
    private ?LoggerInterface $logger; // Le service de logging (optionnel).

    /**
     * Le constructeur est appelé lors de la création du service par le conteneur de services de Symfony.
     * C'est ici que toute la logique d'initialisation et d'authentification a lieu.
     *
     * @param string $projectDir Injecté automatiquement par Symfony, correspond au chemin racine du projet.
     * @param LoggerInterface|null $logger Le service de logging, injecté si disponible.
     */
    public function __construct(string $projectDir, ?LoggerInterface $logger = null)
    {
        $this->projectDir = $projectDir;
        $this->logger = $logger;

        // On construit les chemins complets vers les fichiers de configuration en se basant sur la racine du projet.
        $this->credentialsPath = $this->projectDir . '/config/google-credentials.json';
        $this->tokenPath = $this->projectDir . '/config/token.json';

        // --- Configuration du client HTTP avec gestion des certificats SSL ---
        // L'API Google nécessite une connexion sécurisée (HTTPS). Guzzle, par défaut, utilise les certificats
        // du système. Si le système est mal configuré, les appels API peuvent échouer. Fournir
        // notre propre fichier de certificats ('cacert.pem') rend la connexion plus robuste.
        $caBundlePath = $this->projectDir . '/cacert.pem';

        if (!file_exists($caBundlePath)) {
            $this->logError("Fichier cacert.pem local non trouvé à: " . $caBundlePath . ". Guzzle utilisera les certificats système.");
            $httpClient = new GuzzleHttpClient(); // Client par défaut
        } else {
            // On configure Guzzle pour utiliser notre fichier de certificats.
            $guzzleClientOptions = ['verify' => $caBundlePath];
            $httpClient = new GuzzleHttpClient($guzzleClientOptions);
            $this->logInfo("Client Guzzle configuré avec cacert.pem local: " . $caBundlePath);
        }

        // --- Initialisation du client Google API ---
        $this->client = new GoogleClient();
        $this->client->setHttpClient($httpClient); // On dit au client Google d'utiliser notre client Guzzle configuré.

        // Vérifie l'existence du fichier de credentials. Sans lui, aucune authentification n'est possible.
        if (!file_exists($this->credentialsPath)) {
            $this->logError("Fichier de credentials Google API non trouvé: " . $this->credentialsPath);
            throw new \RuntimeException("Configuration Google API incomplète: fichier de credentials manquant à " . $this->credentialsPath);
        }
        $this->client->setAuthConfig($this->credentialsPath); // Charge les secrets de l'API.
        $this->client->addScope(GoogleServiceCalendar::CALENDAR); // Définit les permissions demandées (ici, l'accès au calendrier).
        $this->client->setAccessType('offline'); // Crucial pour obtenir un 'refresh_token' qui permet de rafraîchir l'accès sans intervention de l'utilisateur.
        
        // --- Chargement du token d'accès existant ---
        // Si le fichier token.json existe, cela signifie que nous nous sommes déjà authentifiés par le passé.
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            if ($accessToken) {
                $this->client->setAccessToken($accessToken); // On charge le token dans le client.
            } else {
                $this->logWarning("Impossible de décoder le token depuis " . $this->tokenPath);
            }
        } else {
            // Si le token n'existe pas, il faudrait normalement rediriger l'utilisateur vers une page
            // d'autorisation Google. Pour une application serveur, ce token est généralement obtenu
            // une seule fois via un script en ligne de commande.
            $this->logWarning("Fichier token.json non trouvé à " . $this->tokenPath . ". Un nouveau flux d'autorisation est nécessaire.");
        }

        // --- Rafraîchissement automatique du token ---
        // Si le token d'accès est expiré, on tente de le rafraîchir en utilisant le 'refresh_token'.
        if ($this->client->isAccessTokenExpired()) {
            $this->logInfo("Token d'accès Google expiré. Tentative de rafraîchissement.");
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                try {
                    // Le client fait un appel à Google pour obtenir un nouveau token d'accès.
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newAccessToken = $this->client->getAccessToken();
                    // On sauvegarde ce nouveau token dans le fichier pour les prochaines utilisations.
                    if ($newAccessToken) {
                        file_put_contents($this->tokenPath, json_encode($newAccessToken));
                        $this->logInfo("Token d'accès rafraîchi et sauvegardé dans " . $this->tokenPath);
                    } else {
                        $this->logError("fetchAccessTokenWithRefreshToken n'a pas retourné de nouveau token.");
                    }
                } catch (\Exception $e) {
                    $this->logError("Erreur lors du rafraîchissement du token d'accès Google: " . $e->getMessage());
                }
            } else {
                $this->logWarning("Token expiré mais aucun refresh_token disponible pour le rafraîchissement dans " . $this->tokenPath);
            }
        }
        
        // Ultime vérification : si après tout ça nous n'avons pas de token valide, les appels API échoueront.
        if (!$this->client->getAccessToken()) {
            $this->logError("Aucun token d'accès Google valide après initialisation/rafraîchissement.");
        }
        
        // Une fois le client Google authentifié, on peut créer le service spécifique au Calendrier.
        $this->calendarService = new GoogleServiceCalendar($this->client);
    }

    // --- Fonctions de logging privées pour simplifier le code ---
    private function logInfo(string $message): void { if ($this->logger) $this->logger->info('[GoogleCalendarService] ' . $message); else error_log('[INFO GCalService] ' . $message); }
    private function logWarning(string $message): void { if ($this->logger) $this->logger->warning('[GoogleCalendarService] ' . $message); else error_log('[WARN GCalService] ' . $message); }
    private function logError(string $message): void { if ($this->logger) $this->logger->error('[GoogleCalendarService] ' . $message); else error_log('[ERROR GCalService] ' . $message); }


    /**
     * Récupère la liste des événements de l'agenda configuré.
     * @return array Une liste d'événements formatés pour être utilisés par FullCalendar.
     */
    public function getEvents(): array
    {
        // Vérification de sécurité avant de faire l'appel API.
        if (!$this->client->getAccessToken()) {
            $this->logError("getEvents: Tentative d'appel API sans token d'accès valide.");
            return [['title' => 'Erreur Authentification Google', 'start' => date('Y-m-d')]]; // Renvoie un événement d'erreur visible.
        }
        
        $eventsList = [];
        try {
            // Paramètres de la requête : max 250 résultats, triés par date de début, et les événements récurrents sont étendus en événements uniques.
            $optParams = ['maxResults' => 250, 'orderBy' => 'startTime', 'singleEvents' => true];
            // Appel à l'API Google pour lister les événements.
            $results = $this->calendarService->events->listEvents($this->calendarId, $optParams);
            
            // On parcourt les résultats et on les formate dans un tableau simple.
            foreach ($results->getItems() as $event) {
                $eventsList[] = [
                    'id'          => $event->getId(),
                    'title'       => $event->getSummary() ?? 'Sans titre', // 'summary' est le titre pour Google.
                    'start'       => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(), // Gère les événements "journée entière" (getDate) et ceux avec une heure (getDateTime).
                    'end'         => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
                    'description' => $event->getDescription()
                ];
            }
        } catch (\Exception $e) {
            $this->logError("Erreur lors de la récupération des événements Google Calendar: " . $e->getMessage());
            return [['title' => 'Erreur API Google Calendar', 'start' => date('Y-m-d')]];
        }
        return $eventsList;
    }

    /**
     * Crée un nouvel événement dans l'agenda Google.
     * @param array $data Les données de l'événement (title, description, start, end, etc.).
     * @return string|null L'ID de l'événement créé, ou null en cas d'erreur.
     */
    public function createEvent(array $data): ?string
    {
        if (!$this->client->getAccessToken()) { $this->logError("createEvent: No valid access token."); return null; }
        
        $isAllDay = $data['allDay'] ?? false;
        $startDateTime = $data['start']; // Format attendu : 'YYYY-MM-DDTHH:MM:SS'
        $endDateTime = $data['end'];

        $eventData = [
            'summary'     => $data['title'],
            'description' => $data['description'] ?? '',
        ];

        // L'API Google attend un format différent pour les événements "journée entière" (date seule)
        // et les événements avec une heure précise (dateTime + timeZone).
        if ($isAllDay) {
            $eventData['start'] = ['date' => substr($startDateTime, 0, 10)]; // On ne prend que la partie YYYY-MM-DD
            // Pour un événement qui dure toute la journée du 1er, la date de fin doit être le 2 (non inclus).
            $endDateObject = new \DateTime(substr($endDateTime, 0, 10));
            $endDateObject->modify('+1 day');
            $eventData['end'] = ['date' => $endDateObject->format('Y-m-d')];
        } else {
            $eventData['start'] = ['dateTime' => $startDateTime, 'timeZone' => $data['timeZone'] ?? 'Europe/Paris'];
            $eventData['end']   = ['dateTime' => $endDateTime, 'timeZone' => $data['timeZone'] ?? 'Europe/Paris'];
        }
        
        $event = new GoogleCalendarEvent($eventData); // On crée l'objet Event attendu par l'API.

        try {
            // Appel à l'API pour insérer le nouvel événement.
            return $this->calendarService->events->insert($this->calendarId, $event)->getId();
        } catch (\Exception $e) { $this->logError("Error creating Google Calendar event: " . $e->getMessage()); return null; }
    }

    /**
     * Met à jour un événement existant dans l'agenda Google.
     * @param string $eventId L'ID de l'événement à modifier.
     * @param array $data Les nouvelles données de l'événement.
     * @return bool True si la mise à jour a réussi, false sinon.
     */
    public function updateEvent(string $eventId, array $data): bool
    {
        if (!$this->client->getAccessToken()) { $this->logError("updateEvent: No valid access token."); return false; }
        try {
            // 1. On récupère l'événement existant depuis l'API.
            $event = $this->calendarService->events->get($this->calendarId, $eventId);
            // 2. On modifie ses propriétés.
            $event->setSummary($data['title']);
            if (isset($data['description'])) $event->setDescription($data['description']);

            $isAllDay = $data['allDay'] ?? false;

            // Comme pour la création, on doit gérer les deux types de date/heure.
            if ($isAllDay) {
                $start = new GoogleCalendarEventDateTime(['date' => substr($data['start'], 0, 10)]);
                $event->setStart($start);
                $endDateObject = new \DateTime(substr($data['end'], 0, 10));
                $endDateObject->modify('+1 day');
                $end = new GoogleCalendarEventDateTime(['date' => $endDateObject->format('Y-m-d')]);
                $event->setEnd($end);
            } else {
                $start = new GoogleCalendarEventDateTime(['dateTime' => $data['start'], 'timeZone' => $data['timeZone'] ?? 'Europe/Paris']);
                $event->setStart($start);
                $end = new GoogleCalendarEventDateTime(['dateTime' => $data['end'], 'timeZone' => $data['timeZone'] ?? 'Europe/Paris']);
                $event->setEnd($end);
            }
            
            // 3. On renvoie l'objet événement modifié à l'API.
            $this->calendarService->events->update($this->calendarId, $eventId, $event);
            return true;
        } catch (\Exception $e) { $this->logError("Error updating Google Calendar event ($eventId): " . $e->getMessage()); return false; }
    }

    /**
     * Supprime un événement de l'agenda Google.
     * @param string $eventId L'ID de l'événement à supprimer.
     * @return bool True si la suppression a réussi, false sinon.
     */
    public function deleteEvent(string $eventId): bool
    {
        if (!$this->client->getAccessToken()) { $this->logError("deleteEvent: No valid access token."); return false; }
        try {
            // Appel direct à l'API pour supprimer l'événement par son ID.
            $this->calendarService->events->delete($this->calendarId, $eventId);
            return true;
        } catch (\Exception $e) { $this->logError("Error deleting Google Calendar event ($eventId): " . $e->getMessage()); return false; }
    }
}