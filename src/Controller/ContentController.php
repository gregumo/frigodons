<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    #[Route('/', name: 'app_content_home')]
    public function home(): Response
    {
        return $this->render('content/home.html.twig');
    }

    #[Route('/guide-du-benevole', name: 'app_content_volunteer')]
    public function index(): Response
    {
        return $this->render('content/volunteer_guide.html.twig');
    }
}
