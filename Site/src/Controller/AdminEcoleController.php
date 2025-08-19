<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ecole')]
#[IsGranted('ROLE_ADMIN')]
class AdminEcoleController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api/ecoles';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/ajouter', name: 'admin_ecole_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(Request $request): Response
    {
        $error = null;
        $success = null;
        if ($request->isMethod('POST')) {
            $data = [
                'nom' => $request->request->get('nom'),
                'adresse' => $request->request->get('adresse'),
                'ville' => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
            ];
            try {
                $response = $this->httpClient->request('POST', $this->javaApiUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data
                ]);
                if ($response->getStatusCode() === 201) {
                    $success = 'École ajoutée avec succès !';
                } else {
                    $error = $response->getContent(false);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        return $this->render('admin/ecole_ajouter.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }
} 