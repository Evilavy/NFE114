<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EnfantRepository;

/**
 * 🏠 HomeController - Le contrôleur principal de la page d'accueil
 * 
 * Ce contrôleur gère tout ce qui s'affiche sur la page d'accueil :
 * - Les trajets d'aujourd'hui (conduits et réservés)
 * - Les statistiques personnelles (CO2 économisé, points)
 * - Les messages non lus
 * - Les trajets populaires disponibles
 * 
 * Architecture : Symfony + API Java + API Points
 */
class HomeController extends AbstractController
{
    // 🔗 Services externes utilisés
    private $httpClient; // Pour faire des appels HTTP vers les APIs
    private $javaApiUrl = 'http://localhost:8080/demo-api/api'; // API Java (trajets, messages, etc.)
    private $pointsApiUrl = 'http://localhost:5164/api/points'; // API Points (.NET)

    /**
     * Constructeur - Injection de dépendance
     * Symfony nous donne automatiquement un HttpClient pour faire des appels HTTP
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * 🏠 Page d'accueil - Route principale "/"
     * 
     * Cette méthode est appelée quand l'utilisateur va sur la page d'accueil
     * Elle récupère toutes les données nécessaires et les envoie au template
     * 
     * Sécurité : Seuls les utilisateurs connectés (ROLE_USER) peuvent y accéder
     */
    #[Route('/', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(EnfantRepository $enfantRepository): Response
    {
        // 👤 1. RÉCUPÉRATION DE L'UTILISATEUR CONNECTÉ
        $user = $this->getUser(); // Symfony nous donne l'utilisateur connecté
        $userId = $user->getId(); // On récupère son ID pour les requêtes
        
        // 📊 2. INITIALISATION DES DONNÉES PAR DÉFAUT
        // Si une API ne répond pas, on a des valeurs par défaut
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

        // 💰 3. RÉCUPÉRATION DES POINTS DE L'UTILISATEUR
        // Appel à l'API Points (.NET) pour récupérer les points
        try {
            $response = $this->httpClient->request('GET', $this->pointsApiUrl . '/' . $userId);
            if ($response->getStatusCode() === 200) {
                $userData = $response->toArray();
                $userPoints = $userData['points'] ?? 0;
            }
        } catch (\Exception $e) {
            // Si l'API Points ne répond pas, on garde 0 points par défaut
        }

        // 👶 4. RÉCUPÉRATION DE MES ENFANTS VALIDÉS
        // Utilise le repository Symfony (base de données locale)
        $mesEnfants = $enfantRepository->findValidesByUserId($userId);

        // 🚗 5. RÉCUPÉRATION DES TRAJETS QUE JE CONDUIS AUJOURD'HUI
        // Appel à l'API Java pour récupérer mes trajets en tant que conducteur
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/conducteur/' . $userId);
            if ($response->getStatusCode() === 200) {
                $tousLesTrajets = $response->toArray(); // Tous mes trajets (toutes dates)
                $aujourdhui = date('Y-m-d'); // Date d'aujourd'hui au format YYYY-MM-DD
                
                // 🔍 FILTRAGE : On ne garde que les trajets d'aujourd'hui
                foreach ($tousLesTrajets as $trajet) {
                    if ($trajet['dateDepart'] === $aujourdhui) {
                        // 👶 ANALYSE : Quels de mes enfants sont dans ce trajet ?
                        $mesEnfantsDansTrajet = [];
                        if (isset($trajet['enfantsIds']) && is_array($trajet['enfantsIds'])) {
                            foreach ($mesEnfants as $enfant) {
                                if (in_array($enfant->getId(), $trajet['enfantsIds'])) {
                                    $mesEnfantsDansTrajet[] = $enfant; // Mon enfant est dans ce trajet
                                }
                            }
                        }
                        
                        // 🏫 ENRICHISSEMENT : Récupérer les infos de l'école d'arrivée
                        if (isset($trajet['ecoleArriveeId'])) {
                            try {
                                $ecoleResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $trajet['ecoleArriveeId']);
                                if ($ecoleResponse->getStatusCode() === 200) {
                                    $trajet['ecole'] = $ecoleResponse->toArray(); // Infos complètes de l'école
                                } else {
                                    $trajet['ecole'] = ['nom' => 'École inconnue']; // Valeur par défaut
                                }
                            } catch (\Exception $e) {
                                $trajet['ecole'] = ['nom' => 'École inconnue']; // En cas d'erreur
                            }
                        }
                        
                        // 📝 AJOUT DES DONNÉES ENRICHIES
                        $trajet['mesEnfantsDansTrajet'] = $mesEnfantsDansTrajet; // Mes enfants dans ce trajet
                        $trajetsAujourdhui[] = $trajet; // Ajouter à la liste des trajets d'aujourd'hui
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorer l'erreur
        }

        // 🎫 6. RÉCUPÉRATION DE MES RÉSERVATIONS (TRAJETS OÙ JE SUIS PASSAGER)
        // Appel à l'API Java pour récupérer les trajets où j'ai réservé une place
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/user/' . $userId . '/reservations');
            if ($response->getStatusCode() === 200) {
                $toutesReservations = $response->toArray(); // Toutes mes réservations (toutes dates)
                $aujourdhui = date('Y-m-d'); // Date d'aujourd'hui
                
                // 🔍 ÉVITER LES DOUBLONS : Si je conduis un trajet ET j'ai une réservation dessus
                $mesTrajetsIds = array_column($trajetsAujourdhui, 'id'); // IDs des trajets que je conduis
                
                // 🔍 FILTRAGE : On ne garde que les réservations d'aujourd'hui
                foreach ($toutesReservations as $reservation) {
                    if ($reservation['dateDepart'] === $aujourdhui) {
                        // 🚫 ÉVITER LES DOUBLONS : Ne pas afficher 2 fois le même trajet
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

        // 💬 7. RÉCUPÉRATION DES MESSAGES NON LUS
        // Appel à l'API Java pour compter les messages non lus
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $messages = $response->toArray(); // Tous mes messages
                foreach ($messages as $message) {
                    // 🔍 FILTRAGE : Messages reçus ET non lus
                    if ($message['destinataireId'] == $userId && !$message['lu']) {
                        $messagesNonLus++; // Incrémenter le compteur
                    }
                }
            }
        } catch (\Exception $e) {
            // Si l'API Messages ne répond pas, on garde 0 messages non lus
        }

        // 🔍 8. RÉCUPÉRATION DES TRAJETS POPULAIRES (DISPONIBLES POUR RÉSERVER)
        // Appel à l'API Java pour récupérer les trajets disponibles
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/statut/disponible');
            if ($response->getStatusCode() === 200) {
                $trajets = $response->toArray(); // Tous les trajets disponibles
                
                // 🔍 FILTRAGE COMPLEXE : On ne veut que les trajets intéressants
                $trajetsFiltres = [];
                $mesEnfantsIds = array_map(function($enfant) { return $enfant->getId(); }, $mesEnfants); // IDs de mes enfants
                
                foreach ($trajets as $trajet) {
                    // 🚫 EXCLURE : Les trajets que je conduis moi-même
                    if ($trajet['conducteurId'] != $userId) {
                        // 🚫 EXCLURE : Les trajets déjà réservés par moi
                        $enfantsIdsTrajet = isset($trajet['enfantsIds']) && is_array($trajet['enfantsIds']) ? $trajet['enfantsIds'] : [];
                        $intersection = array_intersect($enfantsIdsTrajet, $mesEnfantsIds); // Mes enfants déjà dans ce trajet
                        if (!empty($intersection)) {
                            continue; // Je l'ai déjà réservé, on passe au suivant
                        }
                        
                        // 🔍 VÉRIFIER : S'il y a des places disponibles
                        $nombreEnfants = isset($trajet['enfantsIds']) ? count($trajet['enfantsIds']) : 0; // Enfants déjà inscrits
                        $nombrePlaces = $trajet['nombrePlaces'] ?? 0; // Places totales
                        
                        // ✅ AJOUTER : Seulement s'il reste des places
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

        // 🌱 9. CALCUL DES STATISTIQUES PERSONNELLES (CO2 ÉCONOMISÉ)
        // Pour le CO2, on calcule sur TOUS les trajets (pas seulement aujourd'hui)
        $tousTrajetsConduits = [];
        $toutesReservations = [];
        
        // 📊 RÉCUPÉRATION DE TOUS MES TRAJETS (pour le calcul CO2)
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/conducteur/' . $userId);
            if ($response->getStatusCode() === 200) {
                $tousTrajetsConduits = $response->toArray(); // Tous mes trajets (toutes dates)
            }
        } catch (\Exception $e) {
            // Si l'API ne répond pas, tableau vide
        }
        
        // 📊 RÉCUPÉRATION DE TOUTES MES RÉSERVATIONS (pour le calcul CO2)
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/user/' . $userId . '/reservations');
            if ($response->getStatusCode() === 200) {
                $toutesReservations = $response->toArray(); // Toutes mes réservations (toutes dates)
            }
        } catch (\Exception $e) {
            // Si l'API ne répond pas, tableau vide
        }
        
        // 📈 CALCUL DES STATISTIQUES FINALES
        $statsPersonnelles['trajetsEffectues'] = count($tousTrajetsConduits); // Tous les trajets créés par l'utilisateur
        $statsPersonnelles['co2Economise'] = $this->calculerCO2Economise($tousTrajetsConduits, $toutesReservations, $mesEnfants); // CO2 total
        
        // Debug: Afficher les informations pour le calcul CO2
        error_log("=== DEBUG CO2 CALCULATION ===");
        error_log("Trajets conduits aujourd'hui: " . count($trajetsAujourdhui));
        error_log("Réservations aujourd'hui: " . count($mesReservations));
        error_log("Tous trajets conduits: " . count($tousTrajetsConduits));
        error_log("Toutes réservations: " . count($toutesReservations));
        foreach ($tousTrajetsConduits as $trajet) {
            error_log("Trajet ID: " . $trajet['id'] . ", Date: " . ($trajet['dateDepart'] ?? 'N/A') . ", Enfants: " . json_encode($trajet['enfantsIds'] ?? []));
        }
        foreach ($toutesReservations as $reservation) {
            error_log("Réservation ID: " . $reservation['id'] . ", Date: " . ($reservation['dateDepart'] ?? 'N/A') . ", Enfants: " . json_encode($reservation['enfantsIds'] ?? []));
        }
        error_log("CO2 calculé: " . $statsPersonnelles['co2Economise']);
        error_log("=== FIN DEBUG ===");

        // 🎨 10. RENDU FINAL - ENVOI DES DONNÉES AU TEMPLATE
        // Toutes les données récupérées sont envoyées au template Twig pour l'affichage
        return $this->render('home/index.html.twig', [
            'user' => $user, // Utilisateur connecté
            'userPoints' => $userPoints, // Points de l'utilisateur
            'trajetsAujourdhui' => $trajetsAujourdhui, // Trajets que je conduis aujourd'hui
            'mesReservations' => $mesReservations, // Trajets où je suis passager aujourd'hui
            'mesEnfants' => $mesEnfants, // Mes enfants validés
            'messagesNonLus' => $messagesNonLus, // Nombre de messages non lus
            'trajetsPopulaires' => $trajetsPopulaires, // Trajets disponibles pour réserver
            'stats' => $statsPersonnelles, // Statistiques (trajets effectués, CO2 économisé)
            'dateAujourdhui' => $aujourdhui ?? date('Y-m-d'), // Date d'aujourd'hui
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
            
            // Récupérer les IDs de mes enfants
            $mesEnfantsIds = [];
            try {
                $mesEnfantsResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/enfants/user/' . $userId);
                if ($mesEnfantsResponse->getStatusCode() === 200) {
                    $mesEnfants = $mesEnfantsResponse->toArray();
                    $mesEnfantsIds = array_map(function($enfant) { return $enfant['id']; }, $mesEnfants);
                }
            } catch (\Exception $e) {
                // Ignorer l'erreur, on continue avec un tableau vide
            }
            
            // Calculer le CO2 économisé pour ce trajet
            $emissionParKm = 0.12; // kg CO2 par km
            $co2Economise = $this->calculerCO2TrajetIndividuel($trajet, $emissionParKm, $jeSuisConducteur, $mesEnfantsIds);
            
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
    private function calculerCO2Economise(array $trajetsConduits, array $reservations, array $mesEnfants): float
    {
        $co2Total = 0.0;
        
        // Facteur d'émission moyen d'une voiture : 120g CO2/km
        $emissionParKm = 0.12; // kg CO2 par km
        
        // Récupérer les IDs de mes enfants (utiliser le repository Symfony)
        $mesEnfantsIds = array_map(function($enfant) { return $enfant->getId(); }, $mesEnfants);
        
        error_log("Mes enfants IDs: " . json_encode($mesEnfantsIds));
        
        // Calculer CO2 pour les trajets que je conduis
        foreach ($trajetsConduits as $trajet) {
            $co2Trajet = $this->calculerCO2TrajetIndividuel($trajet, $emissionParKm, true, $mesEnfantsIds);
            $co2Total += $co2Trajet;
            error_log("Trajet ID {$trajet['id']} (conducteur): CO2 = $co2Trajet");
        }
        
        // Calculer CO2 pour mes réservations (trajets où je suis passager)
        foreach ($reservations as $reservation) {
            $co2Reservation = $this->calculerCO2TrajetIndividuel($reservation, $emissionParKm, false, $mesEnfantsIds);
            $co2Total += $co2Reservation;
            error_log("Réservation ID {$reservation['id']} (passager): CO2 = $co2Reservation");
        }
        
        error_log("CO2 Total: $co2Total");
        return round($co2Total, 1);
    }
    
    /**
     * Calcule le CO2 économisé pour un trajet individuel
     */
    private function calculerCO2TrajetIndividuel(array $trajet, float $emissionParKm, bool $jeSuisConducteur, array $mesEnfantsIds = []): float
    {
        // Distance par défaut si pas disponible (estimation moyenne)
        $distance = $trajet['distanceKm'] ?? 15.0; // 15km par défaut
        
        // Nombre d'enfants dans le trajet
        $enfantsIds = is_array($trajet['enfantsIds']) ? $trajet['enfantsIds'] : [];
        $nombreEnfants = count($enfantsIds);
        
        // Calcul de base : distance * émission
        $emissionTrajet = $distance * $emissionParKm;
        
        error_log("Trajet ID: " . ($trajet['id'] ?? 'N/A') . ", Distance: $distance, Enfants: " . json_encode($enfantsIds) . ", Mes enfants: " . json_encode($mesEnfantsIds));
        
        if ($jeSuisConducteur) {
            // Si je conduis : CO2 économisé = émissions évitées par les autres parents
            // Compter seulement les enfants qui ne sont pas à moi
            $enfantsAutresParents = array_diff($enfantsIds, $mesEnfantsIds);
            $nombreEnfantsAutresParents = count($enfantsAutresParents);
            
            // Chaque enfant d'un autre parent évite à ses parents de faire le trajet individuellement
            $co2Economise = $emissionTrajet * $nombreEnfantsAutresParents;
            
            error_log("  Conducteur: Enfants autres parents: " . json_encode($enfantsAutresParents) . " ($nombreEnfantsAutresParents), CO2: $co2Economise");
        } else {
            // Si je suis passager : CO2 économisé = mon trajet individuel évité
            // Je compte mon trajet évité (si j'ai des enfants dans ce trajet)
            $mesEnfantsDansTrajet = array_intersect($enfantsIds, $mesEnfantsIds);
            $nombreMesEnfantsDansTrajet = count($mesEnfantsDansTrajet);
            
            if ($nombreMesEnfantsDansTrajet > 0) {
                // J'ai des enfants dans ce trajet, donc j'évite de faire le trajet moi-même
                $co2Economise = $emissionTrajet;
            } else {
                // Je n'ai pas d'enfants dans ce trajet, pas de CO2 économisé
                $co2Economise = 0;
            }
            
            error_log("  Passager: Mes enfants dans trajet: " . json_encode($mesEnfantsDansTrajet) . " ($nombreMesEnfantsDansTrajet), CO2: $co2Economise");
        }
        
        return $co2Economise;
    }
}