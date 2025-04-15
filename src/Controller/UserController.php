<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/trombinoscope', name: 'user_trombinoscope')]
    public function trombinoscope(UserRepository $userRepository): Response
    {
        // Récupère tous les utilisateurs (les membres)
        $users = $userRepository->findAll();
        
        // Affiche la vue "trombinoscope.html.twig" en passant la liste des utilisateurs
        return $this->render('trombinoscope.html.twig', [
            'users' => $users,
        ]);
    }
}
