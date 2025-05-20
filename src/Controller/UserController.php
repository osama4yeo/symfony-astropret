<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractController
{
    #[Route('/trombinoscope', name: 'user_trombinoscope')]
    public function trombinoscope(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('trombinoscope.html.twig', [
            'users' => $users,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil', name: 'user_profile')]
    public function monProfil(): Response
    {
        $user = $this->getUser();

        $response = $this->render('profil.html.twig', [
            'user' => $user,
        ]);
    
        // Empêche la mise en cache par le navigateur
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
    
        return $response;
    }
}