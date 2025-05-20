<?php
// src/Controller/ReservationController.php
namespace App\Controller;

use App\Entity\Materiel;
use App\Entity\Reservation;
use App\Form\ReservationType; // Utilise le bon formulaire
use App\Repository\MaterielRepository;
use App\Repository\ReservationRepository; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; // Pour gérer matériel non trouvé

#[Route('/reservations')] // Préfixe pour ce contrôleur
class ReservationController extends AbstractController
{
    #[Route('/', name: 'reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        // 1. Vérifier si l'utilisateur est connecté
        // 'IS_AUTHENTICATED_FULLY' est plus strict que 'IS_AUTHENTICATED_REMEMBERED'
        // 'ROLE_USER' est souvent suffisant si c'est le rôle de base après connexion.
        if (!$this->isGranted('ROLE_USER')) { // Ou $this->isGranted('IS_AUTHENTICATED_FULLY')
            // L'utilisateur n'est pas connecté ou n'a pas le rôle de base
            $this->addFlash('warning', 'Veuillez vous connecter pour accéder à la section location.');
            // Rediriger vers la page de connexion
            // Remplace 'app_login' par le nom réel de ta route de connexion
            return $this->redirectToRoute('app_login');
        }
        // 2. L'utilisateur est connecté, vérifier ses rôles spécifiques
        if ($this->isGranted('ROLE_ADMIN')) {
            // L'utilisateur est un administrateur
            // Rediriger vers la page de gestion des prêts/locations
            // Remplace 'admin_reservation_index' par le nom réel de ta route admin de gestion
            // Si 'reservation_index' est ta page de gestion admin, utilise-la.
            return $this->redirectToRoute('admin_reservation_index'); // Exemple: 'admin_reservation_index' ou 'reservation_index'
        }
        return $this->redirectToRoute('reservation_new');
    }

    /**
     * Affiche le formulaire pour créer une nouvelle réservation.
     * Peut accepter un ID de matériel via l'URL pour pré-remplir le formulaire.
     */
    // --- MODIFICATION DE LA ROUTE : Accepte un {materielId} optionnel ---
    #[Route('/new/{materielId?}', name: 'reservation_new', methods: ['GET', 'POST'], requirements: ['materielId' => '\d+'])]
    #[IsGranted('ROLE_USER')] 
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        MaterielRepository $materielRepository, // Injecte le repo pour chercher le matériel
        ?int $materielId = null // Récupère l'ID de la route (peut être null)
    ): Response {
        $reservation = new Reservation();
        $materielPredefini = null; // Variable pour stocker le matériel trouvé

        // --- MODIFICATION : Chercher et pré-remplir le matériel si ID fourni ---
        if ($materielId !== null) {
            $materielPredefini = $materielRepository->find($materielId);

            // Vérifier si le matériel existe ET est libre
            if (!$materielPredefini) {
                 throw $this->createNotFoundException('Le matériel demandé n\'existe pas.');
            }
            if ($materielPredefini->getEtat() !== Materiel::ETAT_LIBRE) {
                $this->addFlash('warning', sprintf('Le matériel "%s" n\'est pas disponible à la location pour le moment.', $materielPredefini->getNom()));
                // Rediriger vers la liste des matériels peut être une bonne option ici
                 return $this->redirectToRoute('app_materiel_index');
            }

            // Pré-remplir l'objet Reservation avec le matériel trouvé
            $reservation->setMateriel($materielPredefini);
        }
        // --- Fin Modification ---

        // Créer le formulaire, lié à l'objet $reservation (qui peut avoir le matériel pré-rempli)
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Reservation $reservationData */
            $reservationData = $form->getData();
            // Récupère le matériel depuis l'objet Reservation (peut être celui pré-rempli ou celui choisi)
            $materiel = $reservationData->getMateriel();

            // === La logique de sauvegarde reste la même ===
            // Re-vérifier l'état au cas où (race condition) - Optionnel mais plus sûr
             if (!$materiel) { // Vérifier si un matériel a bien été sélectionné/défini
                 $this->addFlash('danger', 'Aucun matériel n\'a été spécifié pour la réservation.');
             } elseif ($materiel->getEtat() === Materiel::ETAT_LIBRE) {
                $materiel->setEtat(Materiel::ETAT_LOUE);
                $entityManager->persist($materiel);
                $entityManager->persist($reservationData);
                $entityManager->flush();
                $this->addFlash('success', sprintf(
                    'Réservation pour "%s" enregistrée !',
                    $materiel->getNom()
                ));
                return $this->redirectToRoute('reservation_index'); // Ou app_materiel_index
            } else {
                 $this->addFlash('danger', sprintf(
                    'Le matériel "%s" n\'est plus disponible.',
                    $materiel->getNom()
                 ));
            }
        }

        // Afficher le formulaire (utilisera reservation/new.html.twig)
        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
            // Optionnel: passer le matériel prédéfini au template si on veut afficher son nom
            'materielPredefini' => $materielPredefini
        ]);
    }
    #[Route('/location-redirect', name: 'app_location_dispatch', methods: ['GET'])] // Nom de route différent de l'index
    public function locationDispatch(): Response
    {
        if (!$this->isGranted('ROLE_USER')) { // Vérifie si l'utilisateur est au moins un membre connecté
            $this->addFlash('warning', 'Veuillez vous connecter pour accéder à la section location.');
            return $this->redirectToRoute('app_login'); // Assure-toi que 'app_login' est le nom de ta route de connexion
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            // L'utilisateur est un administrateur
            // Rediriger vers la page de gestion des prêts (peut être reservation_index si elle est pour admin)
            return $this->redirectToRoute('admin_reservation_management'); // NOM DE ROUTE À DÉFINIR POUR LA GESTION ADMIN
        }
        // L'utilisateur est un membre standard (a ROLE_USER mais pas ROLE_ADMIN)
        return $this->redirectToRoute('reservation_new'); // Redirige vers le formulaire pour créer une réservation
    }

    #[Route('/admin/management', name: 'admin_reservation_management', methods: ['GET'])] // Adapte le chemin et le nom
    #[IsGranted('ROLE_ADMIN')]
    public function adminReservationManagement(ReservationRepository $reservationRepository): Response
    {
        // Ici, tu affiches ce que l'admin doit voir pour la gestion
        // Par exemple, la même liste que reservation_index ou une vue plus détaillée
        $reservations = $reservationRepository->findBy([], ['dateDebut' => 'DESC']);

        return $this->render('admin/reservation/management.html.twig', [ // Crée ce template
            'reservations' => $reservations,
        ]);
    }
}