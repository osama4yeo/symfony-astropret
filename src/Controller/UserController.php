<?php

namespace App\Controller;

use App\Form\UserProfileType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

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

    #[Route('/profil', name: 'user_profile')]
    public function monProfil(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('user_trombinoscope');
        }

        return $this->render('/profil.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
