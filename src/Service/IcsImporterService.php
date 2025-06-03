<?php

namespace App\Service;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use ICal\ICal; // on utilisera une bibliothèque externe
use DateTimeZone;

class IcsImporterService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function importFromUrl(string $url): array
    {
        $ics = new ICal($url, [ 'defaultSpan'           => 2,     // durée par défaut en heures
                                'defaultTimeZone'       => 'Europe/Paris',
                                'skipRecurrence'        => false,
                                'useTimeZoneWithRRules' => false
                              ]);

        $events = $ics->events();
        $added = 0;
        $skipped = 0;

        foreach ($events as $icsEvent) {
            $existing = $this->em->getRepository(Event::class)->findOneBy(['uid' => $icsEvent->uid]);

            if ($existing) {
                $skipped++;
                continue;
            }

            $event = new Event();
            $event->setUid($icsEvent->uid);
            $event->setSource('ics');
            $event->setTitle($icsEvent->summary ?? 'Sans titre');
            $event->setDescription($icsEvent->description ?? '');
            $event->setStart(new \DateTime($icsEvent->dtstart));
            $event->setEnd(new \DateTime($icsEvent->dtend));
            $event->setAllDay(false);

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
