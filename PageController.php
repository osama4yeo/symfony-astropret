<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/rgpd', name: 'app_page', methods: ['GET'])]
    public function index(): Response
    {
             return $this->render('PAGE_RGPD.html.twig');
    }
} 