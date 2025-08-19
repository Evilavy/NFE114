<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/ecoles')]
class ApiEcoleController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api/ecoles';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('', name: 'api_ecoles_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl);
            $ecoles = json_decode($response->getContent(), true);
            
            return $this->json($ecoles);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération des écoles: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'api_ecoles_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/' . $id);
            
            if ($response->getStatusCode() === 404) {
                return $this->json(['error' => 'École non trouvée'], 404);
            }
            
            $ecole = json_decode($response->getContent(), true);
            return $this->json($ecole);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération de l\'école: ' . $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'api_ecoles_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Validation des données
            if (!isset($data['nom']) || empty($data['nom'])) {
                return $this->json(['error' => 'Le nom de l\'école est obligatoire'], 400);
            }
            
            if (!isset($data['adresse']) || empty($data['adresse'])) {
                return $this->json(['error' => 'L\'adresse de l\'école est obligatoire'], 400);
            }
            
            if (!isset($data['ville']) || empty($data['ville'])) {
                return $this->json(['error' => 'La ville de l\'école est obligatoire'], 400);
            }
            
            if (!isset($data['codePostal']) || empty($data['codePostal'])) {
                return $this->json(['error' => 'Le code postal de l\'école est obligatoire'], 400);
            }
            
            $response = $this->httpClient->request('POST', $this->javaApiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'nom' => $data['nom'],
                    'adresse' => $data['adresse'],
                    'ville' => $data['ville'],
                    'codePostal' => $data['codePostal']
                ]
            ]);
            
            $ecole = json_decode($response->getContent(), true);
            return $this->json($ecole, 201);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la création de l\'école: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'api_ecoles_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Validation des données
            if (!isset($data['nom']) || empty($data['nom'])) {
                return $this->json(['error' => 'Le nom de l\'école est obligatoire'], 400);
            }
            
            if (!isset($data['adresse']) || empty($data['adresse'])) {
                return $this->json(['error' => 'L\'adresse de l\'école est obligatoire'], 400);
            }
            
            if (!isset($data['ville']) || empty($data['ville'])) {
                return $this->json(['error' => 'La ville de l\'école est obligatoire'], 400);
            }
            
            if (!isset($data['codePostal']) || empty($data['codePostal'])) {
                return $this->json(['error' => 'Le code postal de l\'école est obligatoire'], 400);
            }
            
            $response = $this->httpClient->request('PUT', $this->javaApiUrl . '/' . $id, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'nom' => $data['nom'],
                    'adresse' => $data['adresse'],
                    'ville' => $data['ville'],
                    'codePostal' => $data['codePostal']
                ]
            ]);
            
            if ($response->getStatusCode() === 404) {
                return $this->json(['error' => 'École non trouvée'], 404);
            }
            
            $ecole = json_decode($response->getContent(), true);
            return $this->json($ecole);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la mise à jour de l\'école: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'api_ecoles_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $response = $this->httpClient->request('DELETE', $this->javaApiUrl . '/' . $id);
            
            if ($response->getStatusCode() === 404) {
                return $this->json(['error' => 'École non trouvée'], 404);
            }
            
            return $this->json(['message' => 'École supprimée avec succès'], 204);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression de l\'école: ' . $e->getMessage()], 500);
        }
    }
} 