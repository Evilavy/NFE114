<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/points', name: 'user_points', methods: ['GET'])]
    public function getUserPoints(): Response
    {
        $userId = $this->getUser()->getId();
        
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/users/' . $userId . '/points');
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $this->json(['points' => $data['points']]);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
        
        return $this->json(['error' => 'Impossible de récupérer les points'], 500);
    }

    #[Route('/profile', name: 'user_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $userId = $this->getUser()->getId();
        
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/users/' . $userId);
            if ($response->getStatusCode() === 200) {
                $user = $response->toArray();
                return $this->render('user/profile.html.twig', [
                    'user' => $user
                ]);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération du profil: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_home');
    }
} 