<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EnfantRepository;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private $javaApiUrl = 'http://localhost:8080/demo-api/api';
    private $pointsApiUrl = 'http://localhost:5164/api/points';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/trajet/{id}', name: 'reservation_trajet', methods: ['POST'])]
    public function reserverTrajet(Request $request, int $id, EnfantRepository $enfantRepository): Response
    {
        $userId = $this->getUser()->getId();
        $enfantId = $request->request->get('enfantId');
        
        // Convertir en tableau pour la compatibilité avec le reste du code
        $enfantsIds = $enfantId ? [$enfantId] : [];

        try {
            // Valider que des enfants sont sélectionnés
            if (empty($enfantsIds)) {
                $this->addFlash('error', 'Vous devez sélectionner au moins un enfant pour la réservation.');
                return $this->redirectToRoute('trajet_rechercher');
            }

            // Récupérer les détails du trajet
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/' . $id);
            if ($response->getStatusCode() !== 200) {
                $this->addFlash('error', 'Trajet non trouvé');
                return $this->redirectToRoute('trajet_rechercher');
            }
            $trajet = $response->toArray();

            // Vérifier que l'utilisateur n'est pas le conducteur du trajet
            if ($trajet['conducteurId'] === $userId) {
                $this->addFlash('error', "Vous ne pouvez pas réserver votre propre trajet. Vous pouvez ajouter vos enfants directement lors de la création du trajet.");
                return $this->redirectToRoute('trajet_rechercher');
            }

            // Vérifier les places disponibles
            $nombreEnfantsDemandes = count($enfantsIds);
            $placesDisponibles = $trajet['nombrePlaces'];
            
            if ($nombreEnfantsDemandes > $placesDisponibles) {
                $this->addFlash('error', "Pas assez de places disponibles. Vous demandez {$nombreEnfantsDemandes} place(s), mais il n'y en a que {$placesDisponibles} de disponible(s).");
                return $this->redirectToRoute('trajet_rechercher');
            }

            // Valider que tous les enfants appartiennent à l'utilisateur et sont dans la bonne école
            $enfantsValides = [];
            foreach ($enfantsIds as $enfantId) {
                $enfant = $enfantRepository->find($enfantId);
                if (!$enfant) {
                    $this->addFlash('error', "Enfant avec l'ID {$enfantId} non trouvé.");
                    return $this->redirectToRoute('trajet_rechercher');
                }

                // Vérifier que l'enfant appartient à l'utilisateur
                if ($enfant->getUserId() !== $userId) {
                    $this->addFlash('error', "L'enfant {$enfant->getNom()} {$enfant->getPrenom()} ne vous appartient pas.");
                    return $this->redirectToRoute('trajet_rechercher');
                }

                // Valider que l'école du trajet correspond à l'école de l'enfant
                // Note: On compare avec le nom de l'école car l'entité Enfant stocke le nom, pas l'ID
                // Cette logique devra être adaptée selon votre structure de données
                $enfantsValides[] = $enfantId;
            }

            // Vérifier les points de l'utilisateur
            $response = $this->httpClient->request('GET', $this->pointsApiUrl . '/' . $userId);
            if ($response->getStatusCode() !== 200) {
                $this->addFlash('error', 'Impossible de récupérer vos points');
                return $this->redirectToRoute('trajet_rechercher');
            }
            $userData = $response->toArray();
            $userPoints = $userData['points'];
            $pointsCout = $trajet['pointsCout'] ?? 5;
            $coutTotal = $pointsCout * $nombreEnfantsDemandes;

            if ($userPoints < $coutTotal) {
                $this->addFlash('error', "Vous n'avez pas assez de points. Coût total: {$coutTotal} points ({$pointsCout} × {$nombreEnfantsDemandes} enfant(s)), Votre solde: {$userPoints} points");
                return $this->redirectToRoute('trajet_rechercher');
            }

            // Effectuer la réservation (un seul enfant à la fois)
            $enfantId = $enfantsValides[0]; // Prendre le premier enfant
            $response = $this->httpClient->request('POST', $this->javaApiUrl . '/trajets/' . $id . '/reserver', [
                'query' => [
                    'userId' => $userId,
                    'enfantId' => $enfantId,
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $reservationData = $response->toArray();
                
                // Débiter les points côté parent (réservant)
                $response = $this->httpClient->request('POST', $this->pointsApiUrl . '/remove', [
                    'json' => ['id' => $userId, 'points' => $coutTotal]
                ]);

                if ($response->getStatusCode() === 200) {
                    $this->addFlash('success', "Réservation effectuée avec succès ! {$nombreEnfantsDemandes} enfant(s) réservé(s) pour {$coutTotal} points ({$pointsCout} × {$nombreEnfantsDemandes}).");
                } else {
                    $this->addFlash('warning', 'Réservation effectuée, mais erreur lors du débit des points.');
                }

                // Créditer le conducteur: +5 points par enfant réservé qui n'est pas le sien
                try {
                    $conducteurId = $trajet['conducteurId'] ?? null;
                    if ($conducteurId) {
                        $enfantReserve = $enfantRepository->find($enfantId);
                        if ($enfantReserve && $enfantReserve->getUserId() !== $conducteurId) {
                            $pointsPourConducteur = 5 * $nombreEnfantsDemandes;
                            $this->httpClient->request('POST', $this->pointsApiUrl . '/add', [
                                'json' => ['id' => $conducteurId, 'points' => $pointsPourConducteur]
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    // Ne bloque pas la réservation en cas d'erreur de bonus conducteur
                }
            } else {
                $errorContent = $response->getContent(false);
                $this->addFlash('error', 'Erreur lors de la réservation: ' . $errorContent);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la réservation: ' . $e->getMessage());
        }
        return $this->redirectToRoute('trajet_rechercher');
    }
} 