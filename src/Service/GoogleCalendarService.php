<?php

namespace App\Service;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{
    private Google_Service_Calendar $calendarService;
    private string $calendarId = "astropret@gmail.com";

    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../../config/google-credentials.json');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
    
        $tokenPath = __DIR__ . '/../../config/token.json';
    
        if (!file_exists($tokenPath)) {
            throw new \Exception('Fichier token.json manquant.');
        }
    
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    
        // ✅ Rafraîchissement automatique
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            } else {
                throw new \Exception('Token expiré et aucun refresh_token disponible.');
            }
        }
    
        $this->calendarService = new Google_Service_Calendar($client);
    }    

    public function getEvents(): array
    {
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events?key=AIzaSyClXCfieq00CseWzJuPXDybXpDjAW3e7vs";

        $response = @file_get_contents($url);
        if ($response === false) {
            return [['title' => 'Erreur API', 'start' => date('Y-m-d')]];
        }

        $data = json_decode($response, true);
        $events = [];

        foreach ($data['items'] ?? [] as $event) {
            $events[] = [
                'id' => $event['id'],
                'title' => $event['summary'] ?? 'Sans titre',
                'start' => $event['start']['dateTime'] ?? $event['start']['date'],
                'end' => $event['end']['dateTime'] ?? $event['end']['date'],
            ];
        }
        

        return $events;
    }

    public function createEvent(array $data): string
    {
        $event = new Google_Service_Calendar_Event([
            'summary' => $data['title'],
            'description' => $data['description'] ?? '',
            'start' => [
                'dateTime' => $data['start'],
                'timeZone' => 'Europe/Paris',
            ],
            'end' => [
                'dateTime' => $data['end'],
                'timeZone' => 'Europe/Paris',
            ],
        ]);

        $createdEvent = $this->calendarService->events->insert($this->calendarId, $event);
        return $createdEvent->getId();
    }

    public function updateEvent(string $eventId, array $data): void
    {
        $event = $this->calendarService->events->get($this->calendarId, $eventId);

        $event->setSummary($data['title']);
        $event->setDescription($data['description'] ?? '');

        $event->setStart(new \Google_Service_Calendar_EventDateTime([
            'dateTime' => $data['start'],
            'timeZone' => 'Europe/Paris',
        ]));

        $event->setEnd(new \Google_Service_Calendar_EventDateTime([
            'dateTime' => $data['end'],
            'timeZone' => 'Europe/Paris',
        ]));

        $this->calendarService->events->update($this->calendarId, $eventId, $event);
    }

    public function deleteEvent(string $eventId): void
    {
        $this->calendarService->events->delete($this->calendarId, $eventId);
    }

}