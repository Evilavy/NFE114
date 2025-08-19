<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EnfantRepository;

class HomeController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';
    private $pointsApiUrl = 'http://localhost:5164/api/points';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(EnfantRepository $enfantRepository): Response
    {
        $user = $this->getUser();
        $userId = $user->getId();
        
        // Données par défaut
        $userPoints = 0;
        $trajetsAujourdhui = [];
        $mesReservations = [];
        $mesEnfants = [];
        $messagesNonLus = 0;
        $trajetsPopulaires = [];
        $statsPersonnelles = [
            'trajetsEffectues' => 0,
            'co2Economise' => 0
        ];

        try {
            // Récupérer les points de l'utilisateur
            $response = $this->httpClient->request('GET', $this->pointsApiUrl . '/' . $userId);
            if ($response->getStatusCode() === 200) {
                $userData = $response->toArray();
                $userPoints = $userData['points'] ?? 0;
            }
        } catch (\Exception $e) {
            // Points par défaut en cas d'erreur
        }

        // Récupérer les enfants validés de l'utilisateur
        $mesEnfants = $enfantRepository->findValidesByUserId($userId);

        try {
            // Récupérer les trajets que je conduis aujourd'hui
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/conducteur/' . $userId);
            if ($response->getStatusCode() === 200) {
                $tousLesTrajets = $response->toArray();
                $aujourdhui = date('Y-m-d');
                
                foreach ($tousLesTrajets as $trajet) {
                    if ($trajet['dateDepart'] === $aujourdhui) {
                        // Vérifier si mes enfants sont dans ce trajet
                        $mesEnfantsDansTrajet = [];
                        if (isset($trajet['enfantsIds']) && is_array($trajet['enfantsIds'])) {
                            foreach ($mesEnfants as $enfant) {
                                if (in_array($enfant->getId(), $trajet['enfantsIds'])) {
                                    $mesEnfantsDansTrajet[] = $enfant;
                                }
                            }
                        }
                        
                        // Récupérer les informations de l'école d'arrivée
                        if (isset($trajet['ecoleArriveeId'])) {
                            try {
                                $ecoleResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $trajet['ecoleArriveeId']);
                                if ($ecoleResponse->getStatusCode() === 200) {
                                    $trajet['ecole'] = $ecoleResponse->toArray();
                                } else {
                                    $trajet['ecole'] = ['nom' => 'École inconnue'];
                                }
                            } catch (\Exception $e) {
                                $trajet['ecole'] = ['nom' => 'École inconnue'];
                            }
                        }
                        
                        $trajet['mesEnfantsDansTrajet'] = $mesEnfantsDansTrajet;
                        $trajetsAujourdhui[] = $trajet;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorer l'erreur
        }

        try {
            // Récupérer mes réservations
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/user/' . $userId . '/reservations');
            if ($response->getStatusCode() === 200) {
                $toutesReservations = $response->toArray();
                $aujourdhui = date('Y-m-d');
                
                // Créer un tableau des IDs de trajets que je conduis pour éviter les doublons
                $mesTrajetsIds = array_column($trajetsAujourdhui, 'id');
                
                foreach ($toutesReservations as $reservation) {
                    if ($reservation['dateDepart'] === $aujourdhui) {
                        // Ajouter seulement si ce n'est pas un trajet que je conduis déjà
                        if (!in_array($reservation['id'], $mesTrajetsIds)) {
                            // Récupérer les informations de l'école d'arrivée
                            if (isset($reservation['ecoleArriveeId'])) {
                                try {
                                    $ecoleResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $reservation['ecoleArriveeId']);
                                    if ($ecoleResponse->getStatusCode() === 200) {
                                        $reservation['ecole'] = $ecoleResponse->toArray();
                                    } else {
                                        $reservation['ecole'] = ['nom' => 'École inconnue'];
                                    }
                                } catch (\Exception $e) {
                                    $reservation['ecole'] = ['nom' => 'École inconnue'];
                                }
                            }
                            
                            $mesReservations[] = $reservation;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorer l'erreur
        }

        try {
            // Récupérer les messages non lus
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $messages = $response->toArray();
                foreach ($messages as $message) {
                    if ($message['destinataireId'] == $userId && !$message['lu']) {
                        $messagesNonLus++;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorer l'erreur
        }

        try {
            // Récupérer quelques trajets populaires (disponibles)
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/statut/disponible');
            if ($response->getStatusCode() === 200) {
                $trajets = $response->toArray();
                
                // Filtrer pour exclure les trajets de l'utilisateur connecté et les trajets complets
                $trajetsFiltres = [];
                // Préparer la liste des IDs de mes enfants pour filtrer les trajets déjà réservés par moi
                $mesEnfantsIds = array_map(function($enfant) { return $enfant->getId(); }, $mesEnfants);
                foreach ($trajets as $trajet) {
                    if ($trajet['conducteurId'] != $userId) {
                        // Exclure les trajets déjà réservés par l'utilisateur (si l'un de mes enfants est inscrit)
                        $enfantsIdsTrajet = isset($trajet['enfantsIds']) && is_array($trajet['enfantsIds']) ? $trajet['enfantsIds'] : [];
                        $intersection = array_intersect($enfantsIdsTrajet, $mesEnfantsIds);
                        if (!empty($intersection)) {
                            continue; // déjà réservé par moi
                        }
                        // Vérifier s'il y a des places disponibles
                        $nombreEnfants = isset($trajet['enfantsIds']) ? count($trajet['enfantsIds']) : 0;
                        $nombrePlaces = $trajet['nombrePlaces'] ?? 0;
                        
                        // Ajouter seulement s'il y a des places disponibles
                        if ($nombreEnfants < $nombrePlaces) {
                            // Récupérer les informations du conducteur
                            try {
                                $conducteurResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/users/' . $trajet['conducteurId']);
                                if ($conducteurResponse->getStatusCode() === 200) {
                                    $trajet['conducteur'] = $conducteurResponse->toArray();
                                } else {
                                    $trajet['conducteur'] = ['nom' => 'Inconnu', 'prenom' => ''];
                                }
                            } catch (\Exception $e) {
                                $trajet['conducteur'] = ['nom' => 'Inconnu', 'prenom' => ''];
                            }
                            
                            // Récupérer les informations de l'école d'arrivée
                            if (isset($trajet['ecoleArriveeId'])) {
                                try {
                                    $ecoleResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $trajet['ecoleArriveeId']);
                                    if ($ecoleResponse->getStatusCode() === 200) {
                                        $trajet['ecole'] = $ecoleResponse->toArray();
                                    } else {
                                        $trajet['ecole'] = ['nom' => 'École inconnue'];
                                    }
                                } catch (\Exception $e) {
                                    $trajet['ecole'] = ['nom' => 'École inconnue'];
                                }
                            }
                            
                            $trajetsFiltres[] = $trajet;
                        }
                    }
                }
                
                // Prendre les 3 premiers
                $trajetsPopulaires = array_slice($trajetsFiltres, 0, 3);
            }
        } catch (\Exception $e) {
            // Ignorer l'erreur
        }

        // Calculer quelques stats personnelles
        $statsPersonnelles['trajetsEffectues'] = count($trajetsAujourdhui) + count($mesReservations);
        $statsPersonnelles['co2Economise'] = $this->calculerCO2Economise($trajetsAujourdhui, $mesReservations);

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'userPoints' => $userPoints,
            'trajetsAujourdhui' => $trajetsAujourdhui,
            'mesReservations' => $mesReservations,
            'mesEnfants' => $mesEnfants,
            'messagesNonLus' => $messagesNonLus,
            'trajetsPopulaires' => $trajetsPopulaires,
            'stats' => $statsPersonnelles,
            'dateAujourdhui' => $aujourdhui ?? date('Y-m-d'),
        ]);
    }

    #[Route('/api/trajets/{id}/co2', name: 'api_trajet_co2', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getTrajetCO2(int $id): Response
    {
        try {
            // Récupérer le trajet
            $trajetResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $id);
            if ($trajetResponse->getStatusCode() !== 200) {
                return $this->json(['error' => 'Trajet not found'], 404);
            }

            $trajet = $trajetResponse->toArray();
            $userId = $this->getUser()->getId();
            
            // Déterminer si l'utilisateur est conducteur ou passager
            $jeSuisConducteur = $trajet['conducteurId'] == $userId;
            
            // Calculer le CO2 économisé pour ce trajet
            $emissionParKm = 0.12; // kg CO2 par km
            $co2Economise = $this->calculerCO2TrajetIndividuel($trajet, $emissionParKm, $jeSuisConducteur);
            
            return $this->json([
                'co2Economise' => round($co2Economise, 1),
                'distance' => $trajet['distanceKm'] ?? 15.0,
                'nombreEnfants' => is_array($trajet['enfantsIds']) ? count($trajet['enfantsIds']) : 0,
                'jeSuisConducteur' => $jeSuisConducteur
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du calcul CO2: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/trajets/{id}/enfants', name: 'api_trajet_enfants', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getTrajetEnfants(int $id, EnfantRepository $enfantRepository): Response
    {
        try {
            // Récupérer le trajet pour obtenir les IDs des enfants
            $trajetResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $id);
            if ($trajetResponse->getStatusCode() !== 200) {
                return $this->json(['error' => 'Trajet not found'], 404);
            }

            $trajet = $trajetResponse->toArray();
            $enfantsData = [];

            // Si le trajet a des enfants assignés
            if (isset($trajet['enfantsIds']) && is_array($trajet['enfantsIds'])) {
                foreach ($trajet['enfantsIds'] as $enfantId) {
                    $enfant = $enfantRepository->find($enfantId);
                    if ($enfant) {
                        $enfantArray = [
                            'id' => $enfant->getId(),
                            'nom' => $enfant->getNom(),
                            'prenom' => $enfant->getPrenom(),
                            'dateNaissance' => $enfant->getDateNaissance()->format('Y-m-d'),
                            'sexe' => $enfant->getSexe(),
                            'ecole' => $enfant->getEcole(),
                            'userId' => $enfant->getUserId(),
                            'valide' => $enfant->isValide()
                        ];
                        
                        // Récupérer les informations du parent
                        try {
                            $parentResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/users/' . $enfantArray['userId']);
                            if ($parentResponse->getStatusCode() === 200) {
                                $parent = $parentResponse->toArray();
                                $enfantArray['parent'] = $parent;
                            }
                        } catch (\Exception $e) {
                            $enfantArray['parent'] = ['nom' => 'Inconnu', 'prenom' => ''];
                        }

                        $enfantsData[] = $enfantArray;
                    }
                }
            }

            return $this->json($enfantsData);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération des enfants: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/trajets/{id}/details', name: 'api_trajet_details', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getTrajetDetails(int $id): Response
    {
        try {
            // Récupérer les détails du trajet via l'API Java
            $trajetResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $id);
            if ($trajetResponse->getStatusCode() !== 200) {
                return $this->json(['error' => 'Trajet not found'], 404);
            }

            $trajet = $trajetResponse->toArray();
            return $this->json($trajet);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la récupération du trajet: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calcule le CO2 économisé basé sur la distance réelle et le nombre d'enfants
     */
    private function calculerCO2Economise(array $trajetsConduits, array $reservations): float
    {
        $co2Total = 0.0;
        
        // Facteur d'émission moyen d'une voiture : 120g CO2/km
        $emissionParKm = 0.12; // kg CO2 par km
        
        // Calculer CO2 pour les trajets que je conduis
        foreach ($trajetsConduits as $trajet) {
            $co2Total += $this->calculerCO2TrajetIndividuel($trajet, $emissionParKm, true);
        }
        
        // Calculer CO2 pour mes réservations (trajets où je suis passager)
        foreach ($reservations as $reservation) {
            $co2Total += $this->calculerCO2TrajetIndividuel($reservation, $emissionParKm, false);
        }
        
        return round($co2Total, 1);
    }
    
    /**
     * Calcule le CO2 économisé pour un trajet individuel
     */
    private function calculerCO2TrajetIndividuel(array $trajet, float $emissionParKm, bool $jeSuisConducteur): float
    {
        // Distance par défaut si pas disponible (estimation moyenne)
        $distance = $trajet['distanceKm'] ?? 15.0; // 15km par défaut
        
        // Nombre d'enfants dans le trajet
        $nombreEnfants = is_array($trajet['enfantsIds']) ? count($trajet['enfantsIds']) : 0;
        
        // Calcul de base : distance * émission
        $emissionTrajet = $distance * $emissionParKm;
        
        if ($jeSuisConducteur) {
            // Si je conduis : CO2 économisé = émissions évitées par les autres parents
            // Chaque enfant évite à ses parents de faire le trajet individuellement
            $co2Economise = $emissionTrajet * $nombreEnfants;
        } else {
            // Si je suis passager : CO2 économisé = mon trajet individuel évité
            // Divisé par le nombre total de personnes dans la voiture (partage)
            $nombrePersonnes = $nombreEnfants + 1; // +1 pour le conducteur
            $co2Economise = $emissionTrajet / max(1, $nombrePersonnes);
        }
        
        return $co2Economise;
    }
}