<?php
// src/Controller/StaticPageController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaticPageController extends AbstractController
{
    #[Route('/conditions-utilisation-rgpd', name: 'app_rgpd_conditions')]
    public function rgpdConditions(): Response
    {
        return $this->render('registration/PAGE_RGPD.html.twig');
        // Si vous l'avez mis dans templates/registration/ :
        // return $this->render('registration/PAGE_RGPD.html.twig');
    }

    #[Route('/conditions-utilisation-cgu', name: 'app_cgu_conditions')]
    public function cguConditions(): Response
    {
        return $this->render('templates/registration/page_cgu.html.twig');
        // Si vous l'avez mis dans templates/registration/ :
        // return $this->render('registration/PAGE_RGPD.html.twig');
    }

    // Vous pouvez ajouter d'autres routes pour d'autres pages statiques ici
    // #[Route('/mentions-legales', name: 'app_legal_notices')]
    // public function legalNotices(): Response
    // {
    //     return $this->render('static/legal_notices.html.twig');
    // }
}