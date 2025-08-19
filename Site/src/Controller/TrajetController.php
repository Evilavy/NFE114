<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EnfantRepository;

#[Route('/trajet')]
#[IsGranted('ROLE_USER')]
class TrajetController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/ajouter', name: 'trajet_ajouter', methods: ['GET', 'POST'])]
    public function ajouter(Request $request, EnfantRepository $enfantRepository): Response
    {
        $error = null;
        $success = null;
        $ecoles = [];
        $enfants = [];
        $voitures = [];
        $userId = $this->getUser()->getId();

        // Récupérer la liste des écoles (uniquement les écoles validées)
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/validees');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des écoles: " . $e->getMessage();
        }

        // Récupérer les enfants validés de l'utilisateur
        $enfants = $enfantRepository->findValidesByUserId($userId);
        $mesEnfantsIds = array_map(function($e) { return $e->getId(); }, $enfants);

        // Récupérer les voitures de l'utilisateur via l'API Symfony
        try {
            $response = $this->httpClient->request('GET', $this->getParameter('app.base_url') . '/voiture/api/voitures/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $voitures = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des voitures: " . $e->getMessage();
        }

        if ($request->isMethod('POST')) {
            $pointDepart = $request->request->get('pointDepart');
            $ecoleArriveeId = $request->request->get('ecoleArrivee');
            $pointArrivee = null;
            foreach ($ecoles as $ecole) {
                if ($ecole['id'] == $ecoleArriveeId) {
                    $pointArrivee = $ecole['nom'] . ' - ' . $ecole['ville'] . ' (' . $ecole['codePostal'] . ')';
                    break;
                }
            }
            $dateDepart = $request->request->get('dateDepart');
            $heureDepart = $request->request->get('heureDepart');
            $dateArrivee = $request->request->get('dateArrivee');
            $heureArrivee = $request->request->get('heureArrivee');
            $nombrePlaces = $request->request->get('nombrePlaces');
            $enfantsIds = $request->request->all('enfants');
            $voitureId = $request->request->get('voitureId');
            $pointsCout = $request->request->get('pointsCout', 5); // Coût par défaut de 5 points
            $dureeMinutes = $request->request->get('dureeMinutes');
            $distanceKm = $request->request->get('distanceKm');
            $description = $request->request->get('description');

            // Validation que le trajet n'est pas dans le passé
            $dateTimeDepart = $dateDepart . ' ' . $heureDepart;
            $departDateTime = new \DateTime($dateTimeDepart);
            $now = new \DateTime();
            
            if ($departDateTime <= $now) {
                $error = 'Impossible de créer un trajet avec une date/heure de départ dans le passé';
            } elseif ($pointDepart && $pointArrivee && $dateDepart && $heureDepart && $dateArrivee && $heureArrivee && $nombrePlaces && $voitureId) {
                $data = [
                    'pointDepart' => $pointDepart,
                    'pointArrivee' => $pointArrivee,
                    'dateDepart' => $dateDepart,
                    'heureDepart' => $heureDepart,
                    'dateArrivee' => $dateArrivee,
                    'heureArrivee' => $heureArrivee,
                    'nombrePlaces' => (int)$nombrePlaces,
                    'conducteurId' => $userId,
                    'voitureId' => (int)$voitureId,
                    'enfantsIds' => array_map('intval', $enfantsIds),
                    'pointsCout' => (int)$pointsCout,
                    'ecoleArriveeId' => (int)$ecoleArriveeId,
                ];
                
                // Add duration and distance if provided
                if ($dureeMinutes) {
                    $data['dureeMinutes'] = (int)$dureeMinutes;
                }
                if ($distanceKm) {
                    $data['distanceKm'] = (float)$distanceKm;
                }
                if ($description) {
                    $data['description'] = $description;
                }

                try {
                    $response = $this->httpClient->request('POST', $this->javaApiUrl . '/trajets', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $data
                    ]);

                    if ($response->getStatusCode() === 201) {
                        $success = 'Trajet créé avec succès !';
                    } else {
                        $error = $response->getContent(false);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = 'Tous les champs sont obligatoires';
            }
        }

        return $this->render('trajet/ajouter.html.twig', [
            'ecoles' => $ecoles,
            'enfants' => $enfants,
            'voitures' => $voitures,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/rechercher', name: 'trajet_rechercher', methods: ['GET', 'POST'])]
    public function rechercher(Request $request, EnfantRepository $enfantRepository): Response
    {
        $error = null;
        $trajets = [];
        $ecoles = [];
        $enfants = [];
        $userId = $this->getUser()->getId();
        $dateRecherche = $request->request->get('date') ?: $request->query->get('date') ?: date('Y-m-d');
        $ecoleId = $request->request->get('ecoleId') ?: $request->query->get('ecoleId');
        
        // Récupérer la liste des écoles (uniquement les écoles validées)
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/validees');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des écoles: " . $e->getMessage();
        }

        // Récupérer les enfants validés de l'utilisateur
        $enfants = $enfantRepository->findValidesByUserId($userId);
        $mesEnfantsIds = array_map(function($e) { return $e->getId(); }, $enfants);
        
        // Si une école et une date sont sélectionnées (via POST ou GET), rechercher les trajets
        if ($ecoleId && $dateRecherche) {
            try {
                $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets');
                if ($response->getStatusCode() === 200) {
                    $tousTrajets = $response->toArray();
                    
                    // Debug: Afficher les informations de recherche
                    error_log("Recherche trajets - Date: $dateRecherche, Ecole ID: $ecoleId");
                    error_log("Nombre total de trajets récupérés: " . count($tousTrajets));
                    
                    // Filtrer les trajets par école, date, conducteur différent et places disponibles, et non déjà réservés par moi
                    foreach ($tousTrajets as $trajet) {
                        // Vérifier le statut et les critères de base
                        if (
                            $trajet['statut'] === 'disponible' &&
                            $trajet['dateDepart'] === $dateRecherche &&
                            isset($trajet['ecoleArriveeId']) && $trajet['ecoleArriveeId'] == $ecoleId &&
                            isset($trajet['conducteurId']) && $trajet['conducteurId'] != $userId
                        ) {
                            // Exclure les trajets déjà réservés par l'utilisateur (un de ses enfants déjà inscrit)
                            $enfantsIdsTrajet = isset($trajet['enfantsIds']) && is_array($trajet['enfantsIds']) ? $trajet['enfantsIds'] : [];
                            if (!empty(array_intersect($enfantsIdsTrajet, $mesEnfantsIds))) {
                                continue;
                            }
                            // Vérifier s'il y a des places disponibles
                            $nombreEnfants = isset($trajet['enfantsIds']) ? count($trajet['enfantsIds']) : 0;
                            $nombrePlaces = $trajet['nombrePlaces'] ?? 0;
                            
                            // Ajouter le trajet seulement s'il y a des places disponibles
                            if ($nombreEnfants < $nombrePlaces) {
                                // Debug: Afficher les informations du trajet trouvé
                                error_log("Trajet trouvé - ID: {$trajet['id']}, Ecole: {$trajet['ecoleArriveeId']}, Places: $nombreEnfants/$nombrePlaces");
                                
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
                                
                                $trajets[] = $trajet;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $error = "Erreur lors de la récupération des trajets: " . $e->getMessage();
            }
        }
        
        return $this->render('trajet/rechercher.html.twig', [
            'error' => $error,
            'trajets' => $trajets,
            'ecoles' => $ecoles,
            'enfants' => $enfants,
            'dateRecherche' => $dateRecherche,
            'ecoleId' => $ecoleId,
        ]);
    }

    #[Route('/mes-trajets', name: 'trajet_mes_trajets', methods: ['GET'])]
    public function mesTrajets(EnfantRepository $enfantRepository): Response
    {
        $error = null;
        $trajets = [];
        $conversations = [];
        
        $userId = $this->getUser()->getId();
        
        // Récupérer les trajets de l'utilisateur connecté
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/conducteur/' . $userId);
            if ($response->getStatusCode() === 200) {
                $trajets = $response->toArray();
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des trajets: " . $e->getMessage();
        }

        // Récupérer les détails des enfants et conversations pour chaque trajet
        foreach ($trajets as &$trajet) {
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
            // Récupérer les détails des enfants
            $trajet['enfantsDetails'] = [];
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
                                $enfantArray['parent'] = $parentResponse->toArray();
                            }
                        } catch (\Exception $e) {
                            $enfantArray['parent'] = ['nom' => 'Inconnu', 'prenom' => ''];
                        }
                        
                        $trajet['enfantsDetails'][] = $enfantArray;
                    }
                }
            }
            
            // Récupérer les conversations
            try {
                $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/trajet/' . $trajet['id']);
                if ($response->getStatusCode() === 200) {
                    $messages = $response->toArray();
                    if (!empty($messages)) {
                        // Grouper par destinataire
                        $conversationsParTrajet = [];
                        foreach ($messages as $message) {
                            $autreUserId = $message['expediteurId'] == $userId ? $message['destinataireId'] : $message['expediteurId'];
                            if (!isset($conversationsParTrajet[$autreUserId])) {
                                $conversationsParTrajet[$autreUserId] = [];
                            }
                            $conversationsParTrajet[$autreUserId][] = $message;
                        }
                        $conversations[$trajet['id']] = $conversationsParTrajet;
                    }
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs pour les conversations
            }
        }
        
        return $this->render('trajet/mes_trajets.html.twig', [
            'error' => $error,
            'trajets' => $trajets,
            'conversations' => $conversations,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'trajet_supprimer', methods: ['POST'])]
    public function supprimerTrajet(Request $request, int $id, EnfantRepository $enfantRepository): Response
    {
        $userId = $this->getUser()->getId();

        try {
            // Vérifier que le trajet appartient à l'utilisateur connecté
            $trajetResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $id);
            
            if ($trajetResponse->getStatusCode() === 200) {
                $trajet = $trajetResponse->toArray();
                
                // Vérifier que l'utilisateur est bien le conducteur
                if ($trajet['conducteurId'] !== $userId) {
                    $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce trajet.');
                    return $this->redirectToRoute('trajet_mes_trajets');
                }

                // Vérifier si le trajet a des réservations
                $enfantsIds = $trajet['enfantsIds'] ?? [];
                $pointsCout = $trajet['pointsCout'] ?? 5;
                
                if (!empty($enfantsIds)) {
                    // Séparer les enfants du conducteur des autres enfants
                    $enfantsConducteur = [];
                    $enfantsAutresUtilisateurs = [];
                    
                    foreach ($enfantsIds as $enfantId) {
                        $enfant = $enfantRepository->find($enfantId);
                        if ($enfant) {
                            $enfantUserId = $enfant->getUserId();
                            
                            if ($enfantUserId === $userId) {
                                // C'est un enfant du conducteur
                                $enfantsConducteur[] = $enfantId;
                            } else {
                                // C'est un enfant d'un autre utilisateur
                                if (!isset($enfantsAutresUtilisateurs[$enfantUserId])) {
                                    $enfantsAutresUtilisateurs[$enfantUserId] = 0;
                                }
                                $enfantsAutresUtilisateurs[$enfantUserId]++;
                            }
                        }
                    }

                    // Rembourser les points aux utilisateurs qui ont réservé (sauf le conducteur)
                    foreach ($enfantsAutresUtilisateurs as $enfantUserId => $nombreEnfants) {
                        $pointsARembouser = $pointsCout * $nombreEnfants;
                        try {
                            $pointsResponse = $this->httpClient->request('POST', 'http://localhost:5164/api/points/add', [
                                'json' => ['id' => $enfantUserId, 'points' => $pointsARembouser]
                            ]);
                            if ($pointsResponse->getStatusCode() !== 200) {
                                error_log("Erreur remboursement points - User ID: $enfantUserId, Points: $pointsARembouser");
                            }
                        } catch (\Exception $e) {
                            // Log l'erreur mais continuer la suppression
                            error_log("Exception remboursement points - User ID: $enfantUserId, Error: " . $e->getMessage());
                        }
                    }
                }
                
                // Plus de bonus attribué à la création, donc aucune déduction ici

                // Supprimer le trajet
                $deleteResponse = $this->httpClient->request('DELETE', $this->javaApiUrl . '/trajets/' . $id);
                
                if ($deleteResponse->getStatusCode() === 204) {
                    if (!empty($enfantsIds)) {
                        $nombreEnfantsAutres = count($enfantsIds) - count($enfantsConducteur ?? []);
                        $nombreEnfantsConducteur = count($enfantsConducteur ?? []);
                        
                        $this->addFlash('success', "Trajet supprimé avec succès !");
                        
                        // Retirer le bonus acquis sur réservations d'autres utilisateurs (5 points par enfant)
                        if ($nombreEnfantsAutres > 0) {
                            try {
                                $bonusConducteur = 5 * $nombreEnfantsAutres;
                                $this->httpClient->request('POST', 'http://localhost:5164/api/points/remove', [
                                    'json' => ['id' => $userId, 'points' => $bonusConducteur]
                                ]);
                            } catch (\Exception $e) {
                                // ne bloque pas
                            }
                        }
                        
                        if ($nombreEnfantsAutres > 0) {
                            $this->addFlash('info', "Les utilisateurs ayant réservé ({$nombreEnfantsAutres} enfant(s) d'autres utilisateurs) seront remboursés.");
                        }
                        
                        if ($nombreEnfantsConducteur > 0) {
                            $this->addFlash('info', "Note: {$nombreEnfantsConducteur} de vos propres enfant(s) n'ont pas généré de points à rembourser.");
                        }
                        
                        $this->addFlash('info', 'Note: Si l\'API Points est indisponible, les remboursements et déductions seront traités ultérieurement.');
                    } else {
                        $this->addFlash('success', 'Trajet supprimé avec succès !');
                    }
                } else {
                    $this->addFlash('error', 'Erreur lors de la suppression du trajet.');
                }
            } else {
                $this->addFlash('error', 'Trajet non trouvé.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }

        return $this->redirectToRoute('trajet_mes_trajets');
    }
} 