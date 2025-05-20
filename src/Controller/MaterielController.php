<?php

namespace App\Controller;

use App\Entity\Materiel; // Importer l'entité Materiel
use App\Form\MaterielType; // Importer le formulaire (à créer si ce n'est pas fait)
use App\Repository\MaterielRepository; // Importer le Repository
use Doctrine\ORM\EntityManagerInterface; // Importer l'Entity Manager
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // Importer Request
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; // Utilisation des annotations/attributs

#[Route('/materiel')] // Préfixe de route pour toutes les actions de ce contrôleur
final class MaterielController extends AbstractController
{
    /**
     * Affiche la liste de tous les matériels.
     */
    #[Route('/', name: 'app_materiel_index', methods: ['GET'])]
    public function index(MaterielRepository $materielRepository): Response
    {
        // Récupérer tous les matériels via le repository
        $materiels = $materielRepository->findAll();

        // Rendre la vue Twig en passant la liste des matériels
        return $this->render('materiel/index.html.twig', [
            'materiels' => $materiels,
        ]);
    }

    /**
     * Affiche le formulaire pour créer un nouveau matériel et traite sa soumission.
     */
    #[Route('/new', name: 'app_materiel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle instance de Materiel
        $materiel = new Materiel();
        // Créer le formulaire basé sur MaterielType et lié à l'instance $materiel
        $form = $this->createForm(MaterielType::class, $materiel);
        // Traiter la requête HTTP entrante
        $form->handleRequest($request);

        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de $entityManager->persist($materiel); car il est nouveau
            // Mais l'ajouter ne pose pas de problème et est une bonne pratique uniforme
            $entityManager->persist($materiel);

            // Sauvegarder le nouveau matériel en base de données
            $entityManager->flush();

            // Ajouter un message flash pour informer l'utilisateur du succès
            $this->addFlash('success', 'Matériel ajouté avec succès !');

            // Rediriger vers la liste des matériels (ou vers la page de détails du nouveau matériel)
            return $this->redirectToRoute('app_materiel_index', [], Response::HTTP_SEE_OTHER);
            // Ou rediriger vers la page de détails :
            // return $this->redirectToRoute('app_materiel_show', ['id' => $materiel->getId()]);
        }

        // Si la méthode est GET ou si le formulaire n'est pas valide, afficher le formulaire
        return $this->render('materiel/index.html.twig', [
            'materiel' => $materiel, // Optionnel, si la vue en a besoin
            'form' => $form->createView(), // Passer la vue du formulaire au template
        ]);
    }

    /**
     * Affiche les détails d'un matériel spécifique.
     * Utilisation du ParamConverter : Symfony trouve automatiquement le Materiel basé sur {id}
     */
    #[Route('/{id}', name: 'app_materiel_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Materiel $materiel): Response
    {
        // Si l'id ne correspond à aucun Materiel, Symfony lève une erreur 404 automatiquement.
        // Il n'est donc pas nécessaire de vérifier si $materiel est null ici.

        return $this->render('materiel/show.html.twig', [
            'materiel' => $materiel,
        ]);
    }

    /**
     * Affiche le formulaire pour modifier un matériel existant et traite sa soumission.
     */
    #[Route('/{id}/edit', name: 'app_materiel_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Materiel $materiel, EntityManagerInterface $entityManager): Response
    {
        // Le ParamConverter a déjà chargé le $materiel basé sur {id}
        // Créer le formulaire basé sur MaterielType et lié au matériel existant
        $form = $this->createForm(MaterielType::class, $materiel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $materiel est automatiquement mis à jour par le handleRequest
            // Pas besoin de persist() pour un objet déjà géré par Doctrine
            $entityManager->flush(); // Sauvegarder les modifications

            $this->addFlash('success', 'Matériel mis à jour avec succès !');

            return $this->redirectToRoute('app_materiel_index', [], Response::HTTP_SEE_OTHER);
            // Ou rediriger vers la page de détails :
            // return $this->redirectToRoute('app_materiel_show', ['id' => $materiel->getId()]);
        }

        return $this->render('materiel/edit.html.twig', [
            'materiel' => $materiel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un matériel.
     */
    #[Route('/{id}', name: 'app_materiel_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Materiel $materiel, EntityManagerInterface $entityManager): Response
    {
        // Protection CSRF importante pour les suppressions
        // 'delete'.$materiel->getId() est le nom du token attendu
        if ($this->isCsrfTokenValid('delete'.$materiel->getId(), $request->request->get('_token'))) {
            $entityManager->remove($materiel); // Préparer la suppression
            $entityManager->flush(); // Exécuter la suppression en BDD

            $this->addFlash('success', 'Matériel supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }


        return $this->redirectToRoute('app_materiel_index', [], Response::HTTP_SEE_OTHER);
    }
}