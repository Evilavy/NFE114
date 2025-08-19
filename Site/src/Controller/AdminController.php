<?php

namespace App\Controller;

use App\Entity\Enfant;
use App\Repository\EnfantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(EnfantRepository $enfantRepository): Response
    {
        $enfantsEnAttente = $enfantRepository->findEnAttente();
        // Récupération des écoles via l'API Java et filtrage en attente
        $ecolesEnAttente = [];
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles');
            if ($response->getStatusCode() === 200) {
                $toutesEcoles = $response->toArray();
                foreach ($toutesEcoles as $ecole) {
                    $statut = $ecole['statut'] ?? null;
                    $valide = $ecole['valide'] ?? null;
                    if ($statut === 'en_attente' || $valide === false) {
                        $ecolesEnAttente[] = $ecole;
                    }
                }
            }
        } catch (\Exception $e) {
            // ignorer et laisser la liste vide
        }
        
        return $this->render('admin/dashboard.html.twig', [
            'enfantsEnAttente' => $enfantsEnAttente,
            'ecolesEnAttente' => $ecolesEnAttente,
        ]);
    }

    #[Route('/enfants-en-attente', name: 'admin_enfants_attente')]
    public function enfantsEnAttente(EnfantRepository $enfantRepository): Response
    {
        $enfants = $enfantRepository->findEnAttente();

        return $this->render('admin/enfants-attente.html.twig', [
            'enfants' => $enfants,
        ]);
    }

    #[Route('/valider-enfant/{id}', name: 'admin_valider_enfant', methods: ['POST'])]
    public function validerEnfant(Enfant $enfant, EntityManagerInterface $entityManager): Response
    {
        $enfant->setValide(true);
        $entityManager->flush();

        $this->addFlash('success', 'Enfant validé avec succès !');
        return $this->redirectToRoute('admin_enfants_attente');
    }

    #[Route('/rejeter-enfant/{id}', name: 'admin_rejeter_enfant', methods: ['POST'])]
    public function rejeterEnfant(Enfant $enfant, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($enfant);
        $entityManager->flush();

        $this->addFlash('success', 'Enfant rejeté et supprimé.');
        return $this->redirectToRoute('admin_enfants_attente');
    }

    #[Route('/ecoles-en-attente', name: 'admin_ecoles_attente')]
    public function ecolesEnAttente(): Response
    {
        $ecoles = [];
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles');
            if ($response->getStatusCode() === 200) {
                $toutesEcoles = $response->toArray();
                foreach ($toutesEcoles as $ecole) {
                    $statut = $ecole['statut'] ?? null;
                    $valide = $ecole['valide'] ?? null;
                    if ($statut === 'en_attente' || $valide === false) {
                        $ecoles[] = $ecole;
                    }
                }
            }
        } catch (\Exception $e) {
            // ignorer et laisser vide
        }

        return $this->render('admin/ecoles-attente.html.twig', [
            'ecoles' => $ecoles,
        ]);
    }

    #[Route('/ecoles/en-attente', name: 'admin_ecoles_attente_alt')]
    public function ecolesEnAttenteAlt(): Response
    {
        // Redirection vers la route correcte
        return $this->redirectToRoute('admin_ecoles_attente');
    }

    #[Route('/valider-ecole/{id}', name: 'admin_valider_ecole', methods: ['POST'])]
    public function validerEcole(int $id): Response
    {
        try {
            // Tente un endpoint dédié /valider s'il existe
            $resp = $this->httpClient->request('PUT', $this->javaApiUrl . '/ecoles/' . $id . '/valider');
            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                $this->addFlash('success', 'École validée avec succès !');
                return $this->redirectToRoute('admin_ecoles_attente');
            }
        } catch (\Exception $e) {
            // fallback sur mise à jour générique avec statut
        }

        try {
            // Récupérer l'école puis l'envoyer en PUT avec un statut/valide
            $getResp = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $id);
            if ($getResp->getStatusCode() === 200) {
                $ecole = $getResp->toArray();
                $payload = $ecole;
                $payload['statut'] = 'validee';
                $payload['valide'] = true;
                $this->httpClient->request('PUT', $this->javaApiUrl . '/ecoles/' . $id, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $payload,
                ]);
                $this->addFlash('success', 'École validée avec succès !');
            } else {
                $this->addFlash('danger', 'École introuvable.');
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la validation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_ecoles_attente');
    }

    #[Route('/rejeter-ecole/{id}', name: 'admin_rejeter_ecole', methods: ['POST'])]
    public function rejeterEcole(int $id): Response
    {
        try {
            $response = $this->httpClient->request('DELETE', $this->javaApiUrl . '/ecoles/' . $id);
            if ($response->getStatusCode() === 204 || $response->getStatusCode() === 200) {
                $this->addFlash('success', 'École rejetée et supprimée.');
            } else {
                $this->addFlash('danger', 'Échec de la suppression de l\'école.');
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_ecoles_attente');
    }

    #[Route('/ecoles', name: 'admin_ecoles_gestion')]
    public function gestionEcoles(): Response
    {
        $ecoles = [];
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $ecoles = [];
        }

        return $this->render('admin/ecoles_gestion.html.twig', [
            'ecoles' => $ecoles,
        ]);
    }

    #[Route('/ecole/modifier/{id}', name: 'admin_ecole_modifier', methods: ['GET', 'POST'])]
    public function modifierEcole(Request $request, int $id): Response
    {
        $ecole = null;
        try {
            $resp = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $id);
            if ($resp->getStatusCode() === 200) {
                $ecole = $resp->toArray();
            }
        } catch (\Exception $e) {
            $ecole = null;
        }

        if ($request->isMethod('POST')) {
            $payload = [
                'nom' => $request->request->get('nom'),
                'adresse' => $request->request->get('adresse'),
                'codePostal' => $request->request->get('codePostal'),
                'ville' => $request->request->get('ville'),
                'telephone' => $request->request->get('telephone'),
                'email' => $request->request->get('email'),
            ];
            // Si la case est présente, la traduire en booléen
            $valideChecked = $request->request->get('valide') === 'on';
            $payload['valide'] = $valideChecked;
            if (isset($ecole['statut'])) {
                $payload['statut'] = $valideChecked ? 'validee' : ($ecole['statut'] ?? null);
            }

            try {
                $this->httpClient->request('PUT', $this->javaApiUrl . '/ecoles/' . $id, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $payload,
                ]);
                $this->addFlash('success', 'École modifiée avec succès !');
                return $this->redirectToRoute('admin_ecoles_gestion');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la modification: ' . $e->getMessage());
            }
        }

        return $this->render('admin/ecole_modifier.html.twig', [
            'ecole' => $ecole,
        ]);
    }

    #[Route('/ecole/supprimer/{id}', name: 'admin_ecole_supprimer', methods: ['POST'])]
    public function supprimerEcole(int $id): Response
    {
        try {
            $response = $this->httpClient->request('DELETE', $this->javaApiUrl . '/ecoles/' . $id);
            if ($response->getStatusCode() === 204 || $response->getStatusCode() === 200) {
                $this->addFlash('success', 'École supprimée avec succès !');
            } else {
                $this->addFlash('danger', 'Échec de la suppression de l\'école.');
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_ecoles_gestion');
    }
} 