<?php

namespace App\Service;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use ICal\ICal;
use Psr\Log\LoggerInterface; // Ajout du Logger pour tracer les erreurs

class IcsImporterService
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    // On injecte aussi le Logger pour pouvoir enregistrer les erreurs proprement
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function importFromUrl(string $url): array
    {
        try {
            $ics = new ICal($url, [
                'defaultTimeZone' => 'Europe/Paris',
                'skipRecurrence'  => false, // Important pour avoir les événements récurrents
            ]);
        } catch (\Exception $e) {
            // AMÉLIORATION 3 : Gestion des erreurs
            // Si l'URL est invalide ou le fichier corrompu, on attrape l'erreur ici.
            $this->logger->error('Erreur lors de l\'import ICS depuis l\'URL: ' . $url, ['exception' => $e]);
            return ['error' => 'Impossible de lire le calendrier depuis l\'URL fournie.'];
        }

        if (!$ics->hasEvents()) {
            return ['added' => 0, 'skipped' => 0]; // Pas d'événements à traiter
        }
        
        $events = $ics->events();
        
        // --- AMÉLIORATION 2 : Optimisation des requêtes ---
        // 1. On récupère tous les UID du fichier ICS en une seule fois.
        $allUidsFromIcs = array_map(fn($e) => $e->uid, $events);

        // 2. On fait UNE SEULE requête pour trouver tous les événements existants.
        $existingEvents = $this->em->getRepository(Event::class)->findBy(['uid' => $allUidsFromIcs]);
        
        // 3. On crée un tableau de recherche simple pour une vérification rapide.
        $existingUids = [];
        foreach ($existingEvents as $existingEvent) {
            $existingUids[$existingEvent->getUid()] = true;
        }
        // --- Fin de l'optimisation ---
        
        $added = 0;
        $skipped = 0;

        foreach ($events as $icsEvent) {
            // On vérifie maintenant dans notre tableau local, c'est beaucoup plus rapide.
            if (isset($existingUids[$icsEvent->uid])) {
                $skipped++;
                continue;
            }

            $event = new Event();
            $event->setUid($icsEvent->uid);
            $event->setSource('ics'); // Parfait, tu l'avais déjà
            $event->setTitle($icsEvent->summary ?? 'Sans titre');
            $event->setDescription($icsEvent->description ?? '');
            
            // AMÉLIORATION 1 : Détection des événements "toute la journée"
            $isAllDay = !isset($icsEvent->dtstart_tz);
            $event->setAllDay($isAllDay);

            // Le `dtstart` de la librairie est un string, on le convertit en objet DateTime
            $event->setStart(new \DateTime($icsEvent->dtstart, new \DateTimeZone($icsEvent->dtstart_tz ?? 'Europe/Paris')));
            
            // Pour la date de fin, on gère le cas où elle n'existe pas
            if (isset($icsEvent->dtend)) {
                $event->setEnd(new \DateTime($icsEvent->dtend, new \DateTimeZone($icsEvent->dtend_tz ?? 'Europe/Paris')));
            } else {
                // Si pas de date de fin, on peut mettre la même que le début (ou ajouter une durée par défaut)
                $event->setEnd($event->getStart());
            }

            $this->em->persist($event);
            $added++;
        }

        $this->em->flush();

        return [
            'added' => $added,
            'skipped' => $skipped,
        ];
    }
}