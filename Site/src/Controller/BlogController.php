<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('blog/home.html.twig');
    }

    #[Route('/blog', name: 'app_blog')]
    public function index(): Response
    {
        // For now, let's render the same home page for the blog index.
        // The user can change this later.
        return $this->render('blog/index.html.twig');
    }
} 