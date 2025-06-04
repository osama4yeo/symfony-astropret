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
        // Associer l'utilisateur connecté à la réservation
        $user = $this->getUser();
        if ($user) { // S'assurer que l'utilisateur est bien connecté
            $reservation->setUser($user);
        } else {
            // Gérer le cas où l'utilisateur n'est pas connecté, même si IsGranted devrait le prévenir
            $this->addFlash('danger', 'Vous devez être connecté pour faire une réservation.');
            return $this->redirectToRoute('app_login');
        }

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
            // Re-vérifier l'état du matériel au moment de la soumission pour éviter les conditions de course
            $materielActuel = $materielRepository->find($materiel->getId()); // Recharger depuis la BDD
            if ($materielActuel && $materielActuel->getEtat() === Materiel::ETAT_LIBRE) {
                $materielActuel->setEtat(Materiel::ETAT_LOUE);
                // $entityManager->persist($materielActuel); // Pas nécessaire si déjà managé et modifié
                $entityManager->persist($reservation);
                $entityManager->flush();
                $this->addFlash('success', sprintf('Réservation pour "%s" enregistrée !', $materielActuel->getNom()));
                return $this->redirectToRoute('app_materiel_index'); // Ou une page "Mes réservations"
            } else {
                $this->addFlash('danger', sprintf('Le matériel "%s" n\'est plus disponible ou une erreur est survenue.', $materiel?->getNom() ?? 'Inconnu'));
                // Rediriger ou réafficher le formulaire
                return $this->render('reservation/new.html.twig', [
                    'reservation' => $reservation, // Renvoyer l'objet reservation avec les données entrées
                    'form' => $form->createView(),
                    'materielPredefini' => $materielPredefini // Garder le matériel pré-défini si c'était le cas
                ]);
            }
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
            'materielPredefini' => $materielPredefini
        ]);
    }

    // Si vous avez supprimé le dispatch, cette méthode n'est plus appelée par le lien "Location"
    // et pourrait être supprimée si elle n'est plus utilisée ailleurs.
    // Si vous voulez que "Location" mène toujours à app_materiel_index pour tous,
    // modifiez directement le lien dans base.html.twig vers path('app_materiel_index').
    #[Route('/location-redirect', name: 'app_location_dispatch', methods: ['GET'])]
    public function locationDispatch(): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('warning', 'Veuillez vous connecter pour accéder à la section location.');
            return $this->redirectToRoute('app_login');
        }
        // Si vous voulez que tout le monde (admin et membre) aille à la liste du matériel :
        return $this->redirectToRoute('app_materiel_index');

        // Ancien comportement (admin vers liste de prêts, membre vers liste matériel) :
        // if ($this->isGranted('ROLE_ADMIN')) {
        //     return $this->redirectToRoute('admin_reservation_list');
        // }
        // return $this->redirectToRoute('app_materiel_index');
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
            $materiel = $reservation->getMateriel(); // Récupérer le matériel avant de supprimer la réservation
            $reservationId = $reservation->getId(); // Garder l'ID si besoin pour la query (si pas flushé avant)

            $entityManager->remove($reservation);
            $entityManager->flush(); // Flusher la suppression de la réservation d'abord

            if ($materiel) {
                $qb = $reservationRepository->createQueryBuilder('r'); // Récupérer le QueryBuilder
                
                $autresReservationsActives = $qb
                    ->select('COUNT(r.id)')
                    ->where('r.materiel = :materiel')
                    // Utilisation de l'Expression Builder pour la condition OR/AND
                    ->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->gte('r.dateFin', ':now'),       // r.dateFin >= :now
                            $qb->expr()->andX(
                                $qb->expr()->lte('r.dateDebut', ':now'), // ET r.dateDebut <= :now
                                $qb->expr()->gte('r.dateFin', ':now')    // ET r.dateFin >= :now
                            )
                        )
                    )
                    // Optionnel: Exclure explicitement la réservation qu'on vient de supprimer si on ne fait pas confiance au timing du flush
                    // ->andWhere($qb->expr()->neq('r.id', ':deletedReservationId')) 
                    ->setParameter('materiel', $materiel)
                    ->setParameter('now', new \DateTimeImmutable())
                    // ->setParameter('deletedReservationId', $reservationId) // Si la ligne ci-dessus est décommentée
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($autresReservationsActives == 0) {
                    $materiel->setEtat(Materiel::ETAT_LIBRE);
                    // $entityManager->persist($materiel); // Pas besoin si $materiel est déjà managé
                    $entityManager->flush(); // Sauvegarder le changement d'état du matériel
                    $this->addFlash('info', 'L\'état du matériel "' . $materiel->getNom() . '" a été mis à jour à "libre".');
                }
            }
            $this->addFlash('success', 'Réservation supprimée avec succès.');
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
            return $this->redirectToRoute('app_home');
        }

        // S'assurer que le template est au bon endroit (admin/reservation/ ou reservation/)
        return $this->render('admin/reservation/map_view.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}