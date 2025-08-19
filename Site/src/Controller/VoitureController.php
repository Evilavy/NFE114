<?php

namespace App\Controller;

use App\Entity\Voiture;
use App\Repository\VoitureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/voiture')]
class VoitureController extends AbstractController
{
    #[Route('/', name: 'voiture_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(VoitureRepository $voitureRepository): Response
    {
        $user = $this->getUser();
        $voitures = $voitureRepository->findBy(['userId' => $user->getId()]);

        return $this->render('voiture/index.html.twig', [
            'voitures' => $voitures,
        ]);
    }

    #[Route('/{id}/modifier', name: 'voiture_modifier', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function modifier(Request $request, Voiture $voiture, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la voiture
        if ($voiture->getUserId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette voiture');
        }

        // Vérifier si la voiture est utilisée dans un trajet futur
        $trajetsFuturs = [];
        try {
            $httpClient = $this->container->get('http_client');
            $response = $httpClient->request('GET', 'http://localhost:8080/demo-api/api/trajets/voiture/' . $voiture->getId());
            if ($response->getStatusCode() === 200) {
                $tousTrajets = $response->toArray();
                $aujourdhui = date('Y-m-d');
                
                // Filtrer les trajets futurs
                foreach ($tousTrajets as $trajet) {
                    if ($trajet['dateDepart'] >= $aujourdhui && 
                        in_array($trajet['statut'], ['disponible', 'en_cours'])) {
                        $trajetsFuturs[] = $trajet;
                    }
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur API, on continue mais on affiche un avertissement
            $this->addFlash('warning', 'Impossible de vérifier les trajets associés à cette voiture.');
        }

        if ($request->isMethod('POST')) {
            // Vérifier s'il y a des trajets futurs qui utilisent cette voiture
            if (!empty($trajetsFuturs)) {
                $this->addFlash('error', 'Impossible de modifier cette voiture car elle est utilisée dans ' . count($trajetsFuturs) . ' trajet(s) futur(s). Veuillez d\'abord supprimer ou modifier ces trajets.');
                return $this->redirectToRoute('voiture_modifier', ['id' => $voiture->getId()]);
            }

            $marque = $request->request->get('marque');
            $modele = $request->request->get('modele');
            $couleur = $request->request->get('couleur');
            $immatriculation = $request->request->get('immatriculation');
            $nombrePlaces = (int) $request->request->get('nombrePlaces');

            if (empty($marque) || empty($modele) || empty($couleur) || empty($immatriculation) || $nombrePlaces <= 0) {
                $this->addFlash('error', 'Tous les champs sont obligatoires et le nombre de places doit être supérieur à 0');
                return $this->redirectToRoute('voiture_modifier', ['id' => $voiture->getId()]);
            }

            $voiture->setMarque($marque);
            $voiture->setModele($modele);
            $voiture->setCouleur($couleur);
            $voiture->setImmatriculation($immatriculation);
            $voiture->setNombrePlaces($nombrePlaces);

            $entityManager->flush();

            $this->addFlash('success', 'Voiture modifiée avec succès !');
            return $this->redirectToRoute('voiture_index');
        }

        return $this->render('voiture/modifier.html.twig', [
            'voiture' => $voiture,
            'trajetsFuturs' => $trajetsFuturs,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'voiture_supprimer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function supprimer(Voiture $voiture, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la voiture
        if ($voiture->getUserId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette voiture');
        }

        $entityManager->remove($voiture);
        $entityManager->flush();

        $this->addFlash('success', 'Voiture supprimée avec succès !');
        return $this->redirectToRoute('voiture_index');
    }

    #[Route('/api/voitures/user/{userId}', name: 'api_voitures_user', methods: ['GET'])]
    public function getVoituresByUser(int $userId, VoitureRepository $voitureRepository): Response
    {
        $voitures = $voitureRepository->findBy(['userId' => $userId]);
        
        $data = [];
        foreach ($voitures as $voiture) {
            $data[] = [
                'id' => $voiture->getId(),
                'marque' => $voiture->getMarque(),
                'modele' => $voiture->getModele(),
                'couleur' => $voiture->getCouleur(),
                'immatriculation' => $voiture->getImmatriculation(),
                'nombrePlaces' => $voiture->getNombrePlaces(),
                'userId' => $voiture->getUserId(),
            ];
        }
        
        return $this->json($data);
    }
} 