<?php
// src/Controller/AdminGalleryController.php
namespace App\Controller;

use App\Entity\Gallery;
use App\Form\GalleryType;
use App\Repository\GalleryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/gallery')]
#[IsGranted('ROLE_ADMIN')]
class AdminGalleryController extends AbstractController
{
    private string $galleryImagesDirectory;

    public function __construct(string $galleryImagesDirectory)
    {
        $this->galleryImagesDirectory = $galleryImagesDirectory;
    }

    #[Route('/', name: 'admin_gallery_index', methods: ['GET'])]
    public function index(GalleryRepository $galleryRepository): Response
    {
        return $this->render('admin/gallery/index.html.twig', [
            'gallery_items' => $galleryRepository->findBy([], ['updatedAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_gallery_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $galleryItem = new Gallery();
        $form = $this->createForm(GalleryType::class, $galleryItem, ['is_new' => true]);
        $form->handleRequest($request);

        // --- DÉBUT DES DUMPS DE DÉBOGAGE ---
        dump('Requête méthode: ' . $request->getMethod());
        if ($request->isMethod('POST')) { // S'assurer que c'est bien un POST pour les dumps de soumission
            dump('Formulaire soumis (isSubmitted() avant la condition principale) ? : ' . ($form->isSubmitted() ? 'Oui' : 'Non'));
        }
        // --- FIN DES DUMPS DE DÉBOGAGE ---

        if ($form->isSubmitted() && $form->isValid()) {
            // Si on arrive ici, le formulaire est soumis ET valide.
            dump('Le formulaire est soumis ET VALIDE. Traitement en cours...');

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            dump('Fichier image uploadé (getData()):', $imageFile);

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                dump('Nom de fichier généré:', $newFilename);
                try {
                    $imageFile->move($this->galleryImagesDirectory, $newFilename);
                    $galleryItem->setImageFilename($newFilename);
                    dump('Image déplacée avec succès. Nom de fichier dans entité:', $galleryItem->getImageFilename());
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                    dump('FileException lors de l_upload:', $e->getMessage());
                    // Il est important de retourner ici pour afficher le formulaire avec l'erreur
                    return $this->render('admin/gallery/new.html.twig', [
                        'gallery_item' => $galleryItem,
                        'form' => $form->createView(),
                    ]);
                }
            } else if ($form->get('imageFile')->isRequired()) {
                // Cette condition est redondante si la validation de 'required' dans le FormType fonctionne bien,
                // car $form->isValid() serait false. Mais gardons-la pour le debug.
                $this->addFlash('danger', 'Une image est requise pour ajouter une nouvelle entrée à la galerie.');
                dump('Image était requise (is_new=true) mais non fournie, et isValid() a quand même passé ? Étrange.');
                 return $this->render('admin/gallery/new.html.twig', [
                    'gallery_item' => $galleryItem,
                    'form' => $form->createView(),
                ]);
            }

            try {
                $entityManager->persist($galleryItem);
                $entityManager->flush();
                dump('Entité Gallery persistée et flushée avec succès. ID: ' . $galleryItem->getId());
                $this->addFlash('success', 'Image ajoutée à la galerie avec succès.');
                return $this->redirectToRoute('admin_gallery_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                dump('Exception lors du persist/flush:', $e->getMessage(), $e->getTraceAsString());
                $this->addFlash('danger', 'Erreur lors de la sauvegarde en base de données: ' . $e->getMessage());
                // Renvoyer au formulaire pour que l'utilisateur ne perde pas ses données et voie le message
                 return $this->render('admin/gallery/new.html.twig', [
                    'gallery_item' => $galleryItem,
                    'form' => $form->createView(),
                ]);
            }

        } elseif ($form->isSubmitted() && !$form->isValid()) {
            // Si le formulaire est soumis MAIS PAS VALIDE
            dump('Le formulaire a été soumis MAIS N\'EST PAS VALIDE.');
            dump('Erreurs globales du formulaire:', $form->getErrors(false, true)); // Erreurs non liées à un champ spécifique
            dump('Détail des erreurs par champ:', $form->getErrors(true, true)); // Toutes les erreurs, y compris enfants

            // Vous pouvez ajouter un message flash pour indiquer qu'il y a des erreurs
            $this->addFlash('danger', 'Le formulaire contient des erreurs. Veuillez vérifier les champs.');
        }

        return $this->render('admin/gallery/new.html.twig', [
            'gallery_item' => $galleryItem,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_gallery_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Gallery $galleryItem, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $oldImageFilename = $galleryItem->getImageFilename();
        $form = $this->createForm(GalleryType::class, $galleryItem /* Par défaut, is_new sera false ici */);
        $form->handleRequest($request);

        dump('EDIT: Requête méthode: ' . $request->getMethod());
        if ($request->isMethod('POST')) {
            dump('EDIT: Formulaire soumis ? : ' . ($form->isSubmitted() ? 'Oui' : 'Non'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            dump('EDIT: Formulaire soumis ET VALIDE.');
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            dump('EDIT: Fichier image uploadé (getData()):', $imageFile);

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                dump('EDIT: Nom de fichier généré:', $newFilename);
                try {
                    $imageFile->move($this->galleryImagesDirectory, $newFilename);
                    $galleryItem->setImageFilename($newFilename);
                    dump('EDIT: Image déplacée. Ancien fichier:', $oldImageFilename, 'Nouveau:', $newFilename);
                    if ($oldImageFilename && $oldImageFilename !== $newFilename && file_exists($this->galleryImagesDirectory . '/' . $oldImageFilename)) {
                        @unlink($this->galleryImagesDirectory . '/' . $oldImageFilename);
                        dump('EDIT: Ancien fichier image supprimé:', $oldImageFilename);
                    }
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de la nouvelle image : ' . $e->getMessage());
                    dump('EDIT: FileException lors de l_upload:', $e->getMessage());
                }
            }
            
            // $galleryItem->setUpdatedAtValue(); // Géré par PreUpdate de l'entité

            try {
                $entityManager->flush();
                dump('EDIT: Entité Gallery flushée avec succès.');
                $this->addFlash('success', 'Image de la galerie mise à jour avec succès.');
                return $this->redirectToRoute('admin_gallery_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                dump('EDIT: Exception lors du flush:', $e->getMessage(), $e->getTraceAsString());
                $this->addFlash('danger', 'Erreur lors de la sauvegarde en base de données: ' . $e->getMessage());
            }

        } elseif ($form->isSubmitted() && !$form->isValid()) {
            dump('EDIT: Le formulaire a été soumis MAIS N\'EST PAS VALIDE.');
            dump('EDIT: Erreurs globales du formulaire:', $form->getErrors(false, true));
            dump('EDIT: Détail des erreurs par champ:', $form->getErrors(true, true));
            $this->addFlash('danger', 'Le formulaire contient des erreurs. Veuillez vérifier les champs.');
        }

        return $this->render('admin/gallery/edit.html.twig', [
            'gallery_item' => $galleryItem,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_gallery_delete', methods: ['POST'])]
    public function delete(Request $request, Gallery $galleryItem, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$galleryItem->getId(), $request->request->get('_token'))) {
            $imageFilename = $galleryItem->getImageFilename();
            $entityManager->remove($galleryItem);
            $entityManager->flush();
            if ($imageFilename && file_exists($this->galleryImagesDirectory . '/' . $imageFilename)) {
                @unlink($this->galleryImagesDirectory . '/' . $imageFilename);
            }
            $this->addFlash('success', 'Image de la galerie supprimée.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide pour la suppression.');
        }
        return $this->redirectToRoute('admin_gallery_index', [], Response::HTTP_SEE_OTHER);
    }
}