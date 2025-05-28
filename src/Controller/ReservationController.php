<?php
// src/Controller/ReservationController.php
namespace App\Controller;

use App\Entity\Materiel;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\MaterielRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservationController extends AbstractController
{
    /**
     * Page de gestion des réservations pour l'ADMINISTRATEUR.
     */
    #[Route('/admin/reservations', name: 'admin_reservation_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminListReservations(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findBy([], ['dateDebut' => 'DESC']);

        return $this->render('admin/reservation/list.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Formulaire pour créer une nouvelle réservation (pour les MEMBRES).
     */
    #[Route('/reservations/new/{materielId?}', name: 'reservation_new', methods: ['GET', 'POST'], requirements: ['materielId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        MaterielRepository $materielRepository,
        ?int $materielId = null
    ): Response {
        $reservation = new Reservation();
        $materielPredefini = null;
        $reservation->setUser($this->getUser());

        if ($materielId !== null) {
            $materielPredefini = $materielRepository->find($materielId);
            if (!$materielPredefini) {
                $this->addFlash('danger', 'Le matériel demandé n\'existe pas.');
                return $this->redirectToRoute('app_materiel_index');
            }
            if ($materielPredefini->getEtat() !== Materiel::ETAT_LIBRE) {
                $this->addFlash('warning', sprintf('Le matériel "%s" n\'est pas disponible pour le moment.', $materielPredefini->getNom()));
                return $this->redirectToRoute('app_materiel_index');
            }
            $reservation->setMateriel($materielPredefini);
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $materiel = $reservation->getMateriel();
            if (!$materiel) {
                $this->addFlash('danger', 'Aucun matériel n\'a été sélectionné.');
                 return $this->render('reservation/new.html.twig', [
                    'reservation' => $reservation,
                    'form' => $form->createView(),
                    'materielPredefini' => $materielPredefini
                ]);
            }
            $materielActuel = $materielRepository->find($materiel->getId());
            if ($materielActuel && $materielActuel->getEtat() === Materiel::ETAT_LIBRE) {
                $materielActuel->setEtat(Materiel::ETAT_LOUE);
                $entityManager->persist($reservation);
                $entityManager->flush();
                $this->addFlash('success', sprintf('Réservation pour "%s" enregistrée !', $materielActuel->getNom()));
                return $this->redirectToRoute('app_materiel_index');
            } else {
                $this->addFlash('danger', sprintf('Le matériel "%s" n\'est plus disponible ou une erreur est survenue.', $materiel?->getNom() ?? 'Inconnu'));
            }
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
            'materielPredefini' => $materielPredefini
        ]);
    }

    #[Route('/location-redirect', name: 'app_location_dispatch', methods: ['GET'])]
    public function locationDispatch(): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('warning', 'Veuillez vous connecter pour accéder à la section location.');
            return $this->redirectToRoute('app_login');
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_materiel_index');
        }
        return $this->redirectToRoute('app_materiel_index');
    }

    #[Route('/admin/reservations/{id}/edit', name: 'admin_reservation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editReservation(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ReservationType::class, $reservation, ['is_admin_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation #' . $reservation->getId() . ' modifiée.');
            return $this->redirectToRoute('admin_reservation_list');
        }
        return $this->render('admin/reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/reservations/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteReservation(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $materiel = $reservation->getMateriel();
            $entityManager->remove($reservation);
            $entityManager->flush();
            if ($materiel) {
                $autresReservationsActives = $reservationRepository->createQueryBuilder('r')
                    ->select('COUNT(r.id)')
                    ->where('r.materiel = :materiel')
                    ->andWhere('r.dateFin >= :now OR (r.dateDebut <= :now AND r.dateFin >= :now))')
                    ->setParameter('materiel', $materiel)
                    ->setParameter('now', new \DateTimeImmutable())
                    ->getQuery()
                    ->getSingleScalarResult();
                if ($autresReservationsActives == 0) {
                    $materiel->setEtat(Materiel::ETAT_LIBRE);
                    $entityManager->flush();
                    $this->addFlash('info', 'L\'état du matériel "' . $materiel->getNom() . '" a été mis à jour à "libre".');
                }
            }
            $this->addFlash('success', 'Réservation supprimée.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('admin_reservation_list');
    }

    // --- ACTION POUR LA PAGE DE CARTE DÉDIÉE ---
    #[Route('/reservation/{id}/map', name: 'reservation_map_view', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showReservationMap(Reservation $reservation): Response
    {
        if ($reservation->getLatitude() === null || $reservation->getLongitude() === null) {
            $this->addFlash('warning', 'Cette réservation n\'a pas de coordonnées géographiques enregistrées.');
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_reservation_list');
            }
            // Adaptez cette redirection si besoin pour les utilisateurs non-admin
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/reservation/map_view.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}