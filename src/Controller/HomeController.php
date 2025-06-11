<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Repository\GalleryRepository; // AJOUTER CECI
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(GalleryRepository $galleryRepository): Response // Injecter le repository
    {
        $galleryItems = $galleryRepository->findBy(
            ['isActive' => true], // Optionnel: si vous avez un champ isActive
            ['updatedAt' => 'DESC'], // Trier par date de mise à jour, les plus récents en premier
            12 // Limiter à 12 images
        );

        return $this->render('home.html.twig', [
            'gallery_items_data' => $galleryItems, // Passer les images au template
        ]);
    }
}