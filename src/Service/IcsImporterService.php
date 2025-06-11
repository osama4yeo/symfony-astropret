<?php

namespace App\Service;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use ICal\ICal;
use Psr\Log\LoggerInterface;

class IcsImporterService
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function importFromUrl(string $url): array
    {
        // ==================== NOUVELLE AMÉLIORATION ICI ====================
        // On normalise l'URL en remplaçant le pseudo-protocole 'webcal' par 'http'.
        // Cela rend le service compatible avec les liens de type webcal://.
        $url = str_replace('webcal://', 'http://', $url);
        // =====================================================================

        $defaultTimezoneString = 'Europe/Paris';

        try {
            $opts = [
                'http_opts' => [
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ],
            ];
            $ics = new ICal($url, array_merge([
                'defaultTimeZone' => $defaultTimezoneString,
                'skipRecurrence'  => false,
            ], $opts));
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'initialisation ou de la lecture de l\'URL ICS: ' . $url, ['exception' => $e]);
            return ['error' => 'Impossible de lire le calendrier depuis l\'URL fournie. Détail : ' . $e->getMessage()];
        }

        if (!$ics->hasEvents()) {
            return ['added' => 0, 'skipped' => 0, 'message' => 'Aucun événement trouvé dans le calendrier.'];
        }
        
        $events = $ics->events();
        
        $allUidsFromIcs = array_map(fn($e) => $e->uid, $events);
        if (empty($allUidsFromIcs)) {
             return ['added' => 0, 'skipped' => 0, 'message' => 'Aucun événement avec un UID trouvé.'];
        }

        $existingEvents = $this->em->getRepository(Event::class)->findBy(['uid' => $allUidsFromIcs]);
        $existingUids = [];
        foreach ($existingEvents as $existingEvent) {
            $existingUids[$existingEvent->getUid()] = true;
        }
        
        $added = 0;
        $skipped = 0;

        $validTimezones = \DateTimeZone::listIdentifiers();
        $defaultTimezone = new \DateTimeZone($defaultTimezoneString);

        foreach ($events as $icsEvent) {
            if (isset($existingUids[$icsEvent->uid])) {
                $skipped++;
                continue;
            }

            $event = new Event();
            $event->setUid($icsEvent->uid);
            $event->setSource('ics');
            $event->setTitle($icsEvent->summary ?? 'Sans titre');
            $event->setDescription($icsEvent->description ?? ''); // Utilise une chaîne vide au lieu de null

            $startTimezoneToUse = $defaultTimezone;
            if (isset($icsEvent->dtstart_tz) && in_array($icsEvent->dtstart_tz, $validTimezones)) {
                $startTimezoneToUse = new \DateTimeZone($icsEvent->dtstart_tz);
            }
            $event->setStart(new \DateTime($icsEvent->dtstart, $startTimezoneToUse));

            if (isset($icsEvent->dtend)) {
                $endTimezoneToUse = $defaultTimezone;
                if (isset($icsEvent->dtend_tz) && in_array($icsEvent->dtend_tz, $validTimezones)) {
                    $endTimezoneToUse = new \DateTimeZone($icsEvent->dtend_tz);
                }
                $event->setEnd(new \DateTime($icsEvent->dtend, $endTimezoneToUse));
            } else {
                $event->setEnd(clone $event->getStart());
            }

            $isAllDay = strpos($icsEvent->dtstart, 'T') === false;
            $event->setAllDay($isAllDay);

            $this->em->persist($event);
            $added++;
        }

        if ($added > 0) {
            $this->em->flush();
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
        ];
    }
}