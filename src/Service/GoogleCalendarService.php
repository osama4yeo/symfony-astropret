<?php
namespace App\Service;

class GoogleCalendarService
{
    private string $apiKey;
    private string $calendarId;

    public function __construct()
    {
        // Vous pouvez aussi définir ces valeurs dans vos variables d'environnement (.env)
        $this->apiKey = "AIzaSyClXCfieq00CseWzJuPXDybXpDjAW3e7vs";
        $this->calendarId = "astropret@gmail.com"; // ou un autre ID
    }

    public function getEvents(): array
    {
        // Construire l'URL de l'API Google Calendar
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events?key={$this->apiKey}";

        // Récupérer les données depuis l'API
        $response = file_get_contents($url);

        if ($response === FALSE) {
            // En cas d'erreur, retourner un tableau avec l'erreur
            return ["error" => "Impossible de récupérer les événements"];
        }

        $data = json_decode($response, true);
        $formattedEvents = [];

        return $formattedEvents;
    }
}