<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EnfantRepository;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    private $httpClient;
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/conversation/{trajetId}/{destinataireId}', name: 'chat_conversation', methods: ['GET', 'POST'])]
    public function conversation(Request $request, int $trajetId, int $destinataireId, EnfantRepository $enfantRepository): Response
    {
        $error = null;
        $success = null;
        $messages = [];
        $trajet = null;
        
        $userId = $this->getUser()->getId();
        
        // Récupérer les informations du trajet
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $trajetId);
            if ($response->getStatusCode() === 200) {
                $trajet = $response->toArray();
                
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
            } elseif ($response->getStatusCode() === 404) {
                // Le trajet n'existe pas, rediriger vers la liste des messages
                $this->addFlash('error', 'Ce trajet n\'existe plus. La conversation a été supprimée.');
                return $this->redirectToRoute('chat_messages');
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération du trajet: " . $e->getMessage();
        }

        // Récupérer les messages de la conversation
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/conversation/' . $trajetId . '/' . $userId . '/' . $destinataireId);
            if ($response->getStatusCode() === 200) {
                $messages = $response->toArray();
                
                // Marquer comme lus tous les messages reçus par l'utilisateur actuel
                foreach ($messages as $message) {
                    if ($message['destinataireId'] == $userId && !$message['lu']) {
                        try {
                            $this->httpClient->request('PUT', $this->javaApiUrl . '/messages/' . $message['id'] . '/lu');
                        } catch (\Exception $e) {
                            // Ignorer les erreurs de marquage (non critique)
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $error = "Erreur lors de la récupération des messages: " . $e->getMessage();
        }

        // Traitement de l'envoi d'un nouveau message
        if ($request->isMethod('POST')) {
            // Vérifier que le trajet existe avant d'envoyer un message
            if (!$trajet) {
                $this->addFlash('error', 'Impossible d\'envoyer un message : ce trajet n\'existe plus.');
                return $this->redirectToRoute('chat_messages');
            }
            
            // Vérifier que le trajet n'est pas dans le passé
            $dateTrajet = $trajet['dateDepart'];
            $aujourdhui = date('Y-m-d');
            
            if ($dateTrajet < $aujourdhui) {
                $this->addFlash('error', 'Impossible d\'envoyer un message pour un trajet passé.');
                return $this->redirectToRoute('chat_messages');
            }
            
            $contenu = $request->request->get('contenu');
            if ($contenu) {
                $data = [
                    'trajetId' => $trajetId,
                    'expediteurId' => $userId,
                    'destinataireId' => $destinataireId,
                    'contenu' => $contenu,
                    'dateEnvoi' => date('Y-m-d H:i:s'),
                    'lu' => false
                ];
                
                try {
                    $response = $this->httpClient->request('POST', $this->javaApiUrl . '/messages', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $data
                    ]);
                    
                    if ($response->getStatusCode() === 201) {
                        $success = 'Message envoyé !';
                        // Rediriger pour éviter la soumission multiple
                        return $this->redirectToRoute('chat_conversation', [
                            'trajetId' => $trajetId,
                            'destinataireId' => $destinataireId
                        ]);
                    } else {
                        $error = $response->getContent(false);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
        
        // Vérifier si l'utilisateur a déjà réservé ce trajet
        $userHasReserved = false;
        if ($trajet && $trajet['conducteurId'] != $userId) {
            try {
                // Vérifier si l'utilisateur a des enfants dans ce trajet
                $enfantsIds = $trajet['enfantsIds'] ?? [];
                foreach ($enfantsIds as $enfantId) {
                    $enfant = $enfantRepository->find($enfantId);
                    if ($enfant && $enfant->getUserId() == $userId) {
                        $userHasReserved = true;
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de vérification
            }
        }

        return $this->render('chat/conversation.html.twig', [
            'trajetId' => $trajetId,
            'destinataireId' => $destinataireId,
            'trajet' => $trajet,
            'messages' => $messages,
            'error' => $error,
            'success' => $success,
            'userHasReserved' => $userHasReserved,
        ]);
    }

    #[Route('/messages', name: 'chat_messages', methods: ['GET'])]
    public function messages(EnfantRepository $enfantRepository): Response
    {
        $error = null;
        $conversations = [];
        
        $userId = $this->getUser()->getId();
        
        // Récupérer tous les messages de l'utilisateur
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/user/' . $userId);
            if ($response->getStatusCode() === 200) {
                $tousMessages = $response->toArray();
            } else {
                $tousMessages = [];
            }
        } catch (\Exception $e) {
            $tousMessages = [];
        }
        
        // Récupérer tous les trajets pour vérifier leur statut
        try {
            $trajetsResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets');
            $tousTrajets = [];
            if ($trajetsResponse->getStatusCode() === 200) {
                $tousTrajets = $trajetsResponse->toArray();
            }
        } catch (\Exception $e) {
            $tousTrajets = [];
        }
        
        // Créer un index des trajets par ID pour un accès rapide
        $trajetsIndex = [];
        foreach ($tousTrajets as $trajet) {
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
            $trajetsIndex[$trajet['id']] = $trajet;
        }
        
        // Grouper les messages par conversation (trajet + autre utilisateur)
        $conversationsGrouped = [];
        foreach ($tousMessages as $message) {
            $trajetId = $message['trajetId'];
            $autreUserId = $message['expediteurId'] == $userId ? $message['destinataireId'] : $message['expediteurId'];
            $key = $trajetId . '_' . $autreUserId;
            
            // Vérifier si le trajet existe
            $trajet = $trajetsIndex[$trajetId] ?? null;
            
            // Si le trajet n'existe pas, ignorer cette conversation (message orphelin)
            if (!$trajet) {
                continue; // Ignorer cette conversation
            }
            
            // Vérifier si le trajet n'est pas terminé/périmé
            $dateTrajet = $trajet['dateDepart'];
            $aujourdhui = date('Y-m-d');
            
            // Filtrer les trajets terminés, annulés ou périmés
            if ($trajet['statut'] === 'termine' || 
                $trajet['statut'] === 'annule' || 
                $dateTrajet < $aujourdhui) {
                continue; // Ignorer cette conversation
            }
            
            if (!isset($conversationsGrouped[$key])) {
                $conversationsGrouped[$key] = [
                    'trajetId' => $trajetId,
                    'autreUserId' => $autreUserId,
                    'trajet' => $trajet,
                    'messages' => [],
                    'dernierMessage' => null,
                    'messagesNonLus' => 0
                ];
            }
            
            $conversationsGrouped[$key]['messages'][] = $message;
            
            // Compter les messages non lus reçus par l'utilisateur
            if ($message['destinataireId'] == $userId && !$message['lu']) {
                $conversationsGrouped[$key]['messagesNonLus']++;
            }
            
            // Toujours garder le message actuel comme dernier (API Java trie par date ASC, donc le plus récent est à la fin)
            $conversationsGrouped[$key]['dernierMessage'] = $message;
        }
        
        // Ajouter les conversations potentielles (trajets où l'utilisateur peut avoir des interactions)
        foreach ($tousTrajets as $trajet) {
            // Vérifier si le trajet n'est pas terminé/périmé
            $dateTrajet = $trajet['dateDepart'];
            $aujourdhui = date('Y-m-d');
            
            if ($trajet['statut'] === 'termine' || 
                $trajet['statut'] === 'annule' || 
                $dateTrajet < $aujourdhui) {
                continue; // Ignorer ce trajet
            }
            
            $trajetId = $trajet['id'];
            $conducteurId = $trajet['conducteurId'];
            $enfantsIds = $trajet['enfantsIds'] ?? [];
            
            // Si l'utilisateur est conducteur, ajouter les conversations avec les parents des enfants inscrits
            if ($conducteurId == $userId) {
                foreach ($enfantsIds as $enfantId) {
                    $enfant = $enfantRepository->find($enfantId);
                    if ($enfant) {
                        $parentId = $enfant->getUserId();
                        
                        // Ne pas ajouter si c'est l'utilisateur lui-même
                        if ($parentId != $userId) {
                            $key = $trajetId . '_' . $parentId;
                            if (!isset($conversationsGrouped[$key])) {
                                $conversationsGrouped[$key] = [
                                    'trajetId' => $trajetId,
                                    'autreUserId' => $parentId,
                                    'trajet' => $trajet,
                                    'messages' => [],
                                    'dernierMessage' => null,
                                    'messagesNonLus' => 0
                                ];
                            }
                        }
                    }
                }
            }
            // Si l'utilisateur n'est pas conducteur, ajouter la conversation avec le conducteur s'il a des enfants dans ce trajet
            else {
                $userHasChildrenInTrip = false;
                foreach ($enfantsIds as $enfantId) {
                    $enfant = $enfantRepository->find($enfantId);
                    if ($enfant && $enfant->getUserId() == $userId) {
                        $userHasChildrenInTrip = true;
                        break;
                    }
                }
                
                if ($userHasChildrenInTrip) {
                    $key = $trajetId . '_' . $conducteurId;
                    if (!isset($conversationsGrouped[$key])) {
                        $conversationsGrouped[$key] = [
                            'trajetId' => $trajetId,
                            'autreUserId' => $conducteurId,
                            'trajet' => $trajet,
                            'messages' => [],
                            'dernierMessage' => null,
                            'messagesNonLus' => 0
                        ];
                    }
                }
            }
        }
        
        // Trier par date du dernier message (plus récent en premier)
        // Pour les conversations sans messages, utiliser la date du trajet
        uasort($conversationsGrouped, function($a, $b) {
            $dateA = $a['dernierMessage'] ? strtotime($a['dernierMessage']['dateEnvoi']) : strtotime($a['trajet']['dateDepart']);
            $dateB = $b['dernierMessage'] ? strtotime($b['dernierMessage']['dateEnvoi']) : strtotime($b['trajet']['dateDepart']);
            return $dateB - $dateA;
        });
        
        $conversations = $conversationsGrouped;
        
        return $this->render('chat/messages.html.twig', [
            'error' => $error,
            'conversations' => $conversations,
        ]);
    }

    #[Route('/chat/mark-conversation-read/{trajetId}/{autreUserId}', name: 'chat_mark_conversation_read', methods: ['POST'])]
    public function markConversationRead(int $trajetId, int $autreUserId): Response
    {
        $userId = $this->getUser()->getId();
        
        try {
            // Récupérer tous les messages de cette conversation
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages/conversation/' . $trajetId . '/' . $userId . '/' . $autreUserId);
            if ($response->getStatusCode() === 200) {
                $messages = $response->toArray();
                
                // Marquer comme lus tous les messages reçus par l'utilisateur actuel
                foreach ($messages as $message) {
                    if ($message['destinataireId'] == $userId && !$message['lu']) {
                        try {
                            $this->httpClient->request('PUT', $this->javaApiUrl . '/messages/' . $message['id'] . '/lu');
                        } catch (\Exception $e) {
                            // Ignorer les erreurs de marquage individuel
                        }
                    }
                }
                
                return $this->json(['success' => true]);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du marquage des messages'], 500);
        }
        
        return $this->json(['error' => 'Conversation non trouvée'], 404);
    }
} 