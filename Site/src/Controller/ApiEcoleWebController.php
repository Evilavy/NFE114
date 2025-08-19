<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api-ecole')]
class ApiEcoleWebController extends AbstractController
{
    #[Route('', name: 'api_ecole_web_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('api_ecole/index.html.twig');
    }
} 