<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EnfantRepository;

#[Route('/mes-reservations')]
#[IsGranted('ROLE_USER')]
class MesReservationsController extends AbstractController
{
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/', name: 'mes_reservations', methods: ['GET'])]
    public function index(EnfantRepository $enfantRepository): Response
    {
        $userId = $this->getUser()->getId();
        $reservations = [];
        $reservationsActives = [];
        $anciennesReservations = [];
        $error = null;

        try {
            // Récupérer les réservations de l'utilisateur (uniquement les trajets où il est passager, pas conducteur)
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/user/' . $userId . '/reservations');
            
            if ($response->getStatusCode() === 200) {
                $reservations = $response->toArray();
                $aujourdhui = date('Y-m-d');
                
                // Pour chaque réservation, récupérer les détails du conducteur et des enfants
                foreach ($reservations as &$reservation) {
                    try {
                        $conducteurResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/users/' . $reservation['conducteurId']);
                        if ($conducteurResponse->getStatusCode() === 200) {
                            $reservation['conducteur'] = $conducteurResponse->toArray();
                        }
                    } catch (\Exception $e) {
                        $reservation['conducteur'] = ['nom' => 'Inconnu', 'prenom' => ''];
                    }
                    
                    // Récupérer les détails de l'école d'arrivée
                    if (isset($reservation['ecoleArriveeId'])) {
                        try {
                            $ecoleResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $reservation['ecoleArriveeId']);
                            if ($ecoleResponse->getStatusCode() === 200) {
                                $reservation['ecole'] = $ecoleResponse->toArray();
                            }
                        } catch (\Exception $e) {
                            $reservation['ecole'] = ['nom' => 'École inconnue'];
                        }
                    }

                    // Récupérer les détails des enfants de l'utilisateur connecté dans ce trajet
                    $reservation['mesEnfants'] = [];
                    if (isset($reservation['enfantsIds']) && is_array($reservation['enfantsIds'])) {
                        foreach ($reservation['enfantsIds'] as $enfantId) {
                            $enfant = $enfantRepository->find($enfantId);
                            if ($enfant && $enfant->getUserId() == $userId) {
                                $reservation['mesEnfants'][] = $enfant;
                            }
                        }
                    }
                    
                    // Ignorer cette réservation si aucun de mes enfants n'est inscrit (considérée comme annulée pour moi)
                    if (empty($reservation['mesEnfants'])) {
                        continue;
                    }

                    // Séparer les réservations actives des anciennes
                    $dateTrajet = $reservation['dateDepart'];
                    $statut = $reservation['statut'] ?? 'disponible';
                    
                    // Un trajet est considéré comme actif si :
                    // 1. La date est aujourd'hui ou dans le futur ET
                    // 2. Le statut n'est pas 'termine' ou 'annule'
                    if ($dateTrajet >= $aujourdhui && $statut !== 'termine' && $statut !== 'annule') {
                        $reservationsActives[] = $reservation;
                    } else {
                        // Pour les trajets passés, forcer le statut à 'termine' s'il est encore 'disponible'
                        if ($dateTrajet < $aujourdhui && $statut === 'disponible') {
                            $reservation['statut'] = 'termine';
                        }
                        $anciennesReservations[] = $reservation;
                    }
                }
                
                // Trier les réservations actives par date (plus proche en premier)
                usort($reservationsActives, function($a, $b) {
                    return strtotime($a['dateDepart']) - strtotime($b['dateDepart']);
                });
                
                // Trier les anciennes réservations par date (plus récente en premier)
                usort($anciennesReservations, function($a, $b) {
                    return strtotime($b['dateDepart']) - strtotime($a['dateDepart']);
                });
                
            } else {
                $error = 'Erreur lors de la récupération de vos réservations';
            }
        } catch (\Exception $e) {
            $error = 'Erreur lors de la récupération de vos réservations: ' . $e->getMessage();
        }

        return $this->render('mes_reservations/index.html.twig', [
            'reservationsActives' => $reservationsActives,
            'anciennesReservations' => $anciennesReservations,
            'error' => $error,
        ]);
    }

    #[Route('/{trajetId}/message', name: 'mes_reservations_envoyer_message', methods: ['POST'])]
    public function envoyerMessage(Request $request, int $trajetId): Response
    {
        $message = $request->request->get('message');
        $userId = $this->getUser()->getId();

        if (empty($message)) {
            $this->addFlash('error', 'Le message ne peut pas être vide.');
            return $this->redirectToRoute('mes_reservations');
        }

        try {
            // Récupérer les détails du trajet pour obtenir l'ID du conducteur
            $trajetResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $trajetId);
            
            if ($trajetResponse->getStatusCode() === 200) {
                $trajet = $trajetResponse->toArray();
                $conducteurId = $trajet['conducteurId'];
                
                // Vérifier que le trajet n'est pas dans le passé
                $dateTrajet = $trajet['dateDepart'];
                $aujourdhui = date('Y-m-d');
                
                if ($dateTrajet < $aujourdhui) {
                    $this->addFlash('error', 'Impossible d\'envoyer un message pour un trajet passé.');
                    return $this->redirectToRoute('mes_reservations');
                }

                // Envoyer le message au conducteur
                $messageData = [
                    'trajetId' => $trajetId,
                    'expediteurId' => $userId,
                    'destinataireId' => $conducteurId,
                    'contenu' => $message,
                    'dateEnvoi' => date('Y-m-d H:i:s'),
                    'lu' => false
                ];

                $response = $this->httpClient->request('POST', $this->javaApiUrl . '/messages', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $messageData
                ]);

                if ($response->getStatusCode() === 201) {
                    $this->addFlash('success', 'Message envoyé avec succès au conducteur.');
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'envoi du message.');
                }
            } else {
                $this->addFlash('error', 'Trajet non trouvé.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi du message: ' . $e->getMessage());
        }

        return $this->redirectToRoute('mes_reservations');
    }

    #[Route('/{trajetId}/annuler', name: 'mes_reservations_annuler', methods: ['POST'])]
    public function annulerReservation(Request $request, int $trajetId): Response
    {
        $enfantId = $request->request->get('enfant_id');
        $userId = $this->getUser()->getId();

        if (!$enfantId) {
            $this->addFlash('error', 'Enfant non spécifié pour l\'annulation.');
            return $this->redirectToRoute('mes_reservations');
        }

        try {
            // Récupérer les détails du trajet
            $trajetResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $trajetId);
            
            if ($trajetResponse->getStatusCode() === 200) {
                $trajet = $trajetResponse->toArray();
                $pointsCout = $trajet['pointsCout'] ?? 5;

                // Annuler la réservation dans l'API Java
                $response = $this->httpClient->request('POST', $this->javaApiUrl . '/trajets/' . $trajetId . '/annuler-reservation', [
                    'query' => [
                        'userId' => $userId,
                        'enfantId' => $enfantId,
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    // Rembourser le parent
                    $pointsResponse = $this->httpClient->request('POST', 'http://localhost:5164/api/points/add', [
                        'json' => ['id' => $userId, 'points' => $pointsCout]
                    ]);

                    // Retirer le bonus conducteur si applicable
                    try {
                        $conducteurId = $trajet['conducteurId'] ?? null;
                        if ($conducteurId && (int)$conducteurId !== (int)$userId) {
                            $this->httpClient->request('POST', 'http://localhost:5164/api/points/remove', [
                                'json' => ['id' => $conducteurId, 'points' => 5]
                            ]);
                        }
                    } catch (\Exception $e) {
                        // ignorer erreurs côté bonus
                    }

                    if ($pointsResponse->getStatusCode() === 200) {
                        $this->addFlash('success', 'Réservation annulée avec succès ! ' . $pointsCout . ' points vous ont été remboursés.');
                    } else {
                        $this->addFlash('warning', 'Réservation annulée, mais erreur lors du remboursement des points.');
                    }
                } else {
                    $errorContent = $response->getContent(false);
                    $this->addFlash('error', 'Erreur lors de l\'annulation: ' . $errorContent);
                }
            } else {
                $this->addFlash('error', 'Trajet non trouvé.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('mes_reservations');
    }
}