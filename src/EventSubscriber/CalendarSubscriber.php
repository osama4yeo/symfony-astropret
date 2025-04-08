<?php
namespace App\EventSubscriber;

use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // Indique que cette classe écoute l'événement SetDataEvent
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }
}
