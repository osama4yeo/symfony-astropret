<?php

namespace App\Controller;

use App\Entity\Materiel;
use App\Form\MaterielType;
use App\Repository\MaterielRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MaterielController extends AbstractController
{
    /**
     * Affiche la liste publique du matériel pour les utilisateurs.
     */
    #[Route('/materiel', name: 'app_materiel_index', methods: ['GET'])]
    public function indexPublic(MaterielRepository $materielRepository): Response
    {
        // Ce template est pour l'affichage public, il est bien dans templates/materiel/index.html.twig
        return $this->render('materiel/index.html.twig', [
            'materiels' => $materielRepository->findAll(),
        ]);
    }

    /**
     * Affiche la liste des matériels pour l'ADMINISTRATION.
     */
    #[Route('/admin/materiel', name: 'admin_materiel_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminList(MaterielRepository $materielRepository): Response
    {
        // --- MODIFICATION DU CHEMIN DU TEMPLATE ---
        return $this->render('materiel/list.html.twig', [ // Au lieu de 'admin/materiel/list.html.twig'
            'materiels' => $materielRepository->findAll(),
        ]);
    }

    /**
     * Affiche le formulaire pour créer un nouveau matériel et traite sa soumission.
     */
    #[Route('/admin/materiel/new', name: 'admin_materiel_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        string $materielsDirectory
    ): Response {
        $materiel = new Materiel();
        $form = $this->createForm(MaterielType::class, $materiel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($materielsDirectory, $newFilename);
                    $materiel->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                    // --- MODIFICATION DU CHEMIN DU TEMPLATE ---
                    return $this->render('materiel/new.html.twig', [ // Au lieu de 'admin/materiel/new.html.twig'
                        'materiel' => $materiel,
                        'form' => $form->createView(),
                    ]);
                }
            }

            $entityManager->persist($materiel);
            $entityManager->flush();

            $this->addFlash('success', 'Nouveau matériel ajouté avec succès !');
            return $this->redirectToRoute('admin_materiel_list', [], Response::HTTP_SEE_OTHER);
        }

        // --- MODIFICATION DU CHEMIN DU TEMPLATE ---
        return $this->render('materiel/new.html.twig', [ // Au lieu de 'admin/materiel/new.html.twig'
            'materiel' => $materiel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche les détails d'un matériel spécifique (ADMIN).
     * Si vous n'utilisez pas ce template de show admin, vous pouvez supprimer cette action.
     */
    #[Route('/admin/materiel/{id}', name: 'admin_materiel_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Materiel $materiel): Response
    {
        // --- MODIFICATION DU CHEMIN DU TEMPLATE ---
        return $this->render('materiel/show.html.twig', [ // Au lieu de 'admin/materiel/show.html.twig'
                                                        // Assurez-vous que ce fichier existe dans templates/materiel/
            'materiel' => $materiel,
        ]);
    }

    /**
     * Affiche le formulaire pour modifier un matériel existant et traite sa soumission.
     */
    #[Route('/admin/materiel/{id}/edit', name: 'admin_materiel_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Materiel $materiel,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        string $materielsDirectory
    ): Response {
        $oldImageFilename = $materiel->getImageFilename();
        $form = $this->createForm(MaterielType::class, $materiel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($materielsDirectory, $newFilename);
                    $materiel->setImageFilename($newFilename);
                    if ($oldImageFilename && $oldImageFilename !== $newFilename && file_exists($materielsDirectory . '/' . $oldImageFilename)) {
                        @unlink($materielsDirectory . '/' . $oldImageFilename);
                    }
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de la nouvelle image : ' . $e->getMessage());
                    // --- MODIFICATION DU CHEMIN DU TEMPLATE ---
                    return $this->render('materiel/edit.html.twig', [ // Au lieu de 'admin/materiel/edit.html.twig'
                        'materiel' => $materiel,
                        'form' => $form->createView(),
                    ]);
                }
            }
            $entityManager->flush();
            $this->addFlash('success', 'Matériel mis à jour avec succès !');
            return $this->redirectToRoute('admin_materiel_list', [], Response::HTTP_SEE_OTHER);
        }

        // --- MODIFICATION DU CHEMIN DU TEMPLATE ---
        return $this->render('materiel/edit.html.twig', [ // Au lieu de 'admin/materiel/edit.html.twig'
            'materiel' => $materiel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un matériel.
     * (Pas de rendu de template ici, donc pas de changement de chemin)
     */
    #[Route('/admin/materiel/{id}/delete', name: 'admin_materiel_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Materiel $materiel,
        EntityManagerInterface $entityManager,
        string $materielsDirectory
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$materiel->getId(), $request->request->get('_token'))) {
            $imageFilename = $materiel->getImageFilename();
            $entityManager->remove($materiel);
            $entityManager->flush();
            if ($imageFilename && file_exists($materielsDirectory.'/'.$imageFilename)) {
                try {
                    unlink($materielsDirectory.'/'.$imageFilename);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Le matériel a été supprimé, mais une erreur est survenue lors de la suppression du fichier image.');
                }
            }
            $this->addFlash('success', 'Matériel supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('admin_materiel_list', [], Response::HTTP_SEE_OTHER);
    }
}