<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil/modifier', name: 'user_profile_edit')]
    public function editProfil(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre profil a bien été mis à jour.');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
