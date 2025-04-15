<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class EventController extends AbstractController
{
    // Affichage du calendrier
    #[Route('/event', name: 'app_event')]
    public function index(): Response
    {
        return $this->render('event/index.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }

    // Création d'un événement via l'API (avec FullCalendar)
    #[Route('/api/events', name: 'api_event_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data['start']) || !isset($data['end'])) {
            return new JsonResponse(['error' => 'Dates manquantes'], 400);
        }
    
        $event = new Event();
        $event->setTitle($data['title'] ?? 'Sans titre');
        $event->setStart(new \DateTime($data['start']));
        $event->setEnd(new \DateTime($data['end']));
        $event->setDescription($data['description'] ?? '');
    
        $entityManager->persist($event);
        $entityManager->flush();
    
        return new JsonResponse([
            'status' => 'created',
            'id' => $event->getId()
        ]);
    }
    #[Route('/api/events', name: 'api_events_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAll();

        $data = array_map(function ($event) {
            return [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getStart()->format('Y-m-d\TH:i:s'),
                'end' => $event->getEnd()->format('Y-m-d\TH:i:s'),
                'description' => $event->getDescription(),
            ];
        }, $events);

        return new JsonResponse($data);
    }
    
}
