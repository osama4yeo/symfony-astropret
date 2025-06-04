<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class cguController extends AbstractController
{
    #[Route('/CGU', name: 'app_CGU', methods: ['GET'])]
    public function index(): Response
    {
             return $this->render('PAGE_CGU.html.twig');
    }
} 