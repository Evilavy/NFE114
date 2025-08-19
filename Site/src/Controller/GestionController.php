<?php

namespace App\Controller;

use App\Entity\Voiture;
use App\Entity\Enfant;
use App\Repository\VoitureRepository;
use App\Repository\EnfantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/gestion')]
class GestionController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $javaApiUrl;

    public function __construct(HttpClientInterface $httpClient, string $javaApiUrl = 'http://localhost:8080/demo-api/api')
    {
        $this->httpClient = $httpClient;
        $this->javaApiUrl = $javaApiUrl;
    }

    #[Route('/mes-enfants', name: 'gestion_enfants', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function mesEnfants(
        Request $request, 
        EnfantRepository $enfantRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $error = null;
        $success = null;
        $userId = $this->getUser()->getId();

        // Récupérer les enfants de l'utilisateur
        $enfants = $enfantRepository->findByUserId($userId);

        // Récupérer les écoles validées via l'API Java
        $ecoles = [];
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/validees');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $ecoles = [];
        }

        // Gestion de la suppression d'enfant
        if ($request->isMethod('POST') && $request->request->get('action') === 'supprimer_enfant') {
            $enfantId = $request->request->get('enfant_id');
            
            $enfant = $enfantRepository->find($enfantId);
            if (!$enfant) {
                $error = 'Enfant non trouvé.';
            } elseif ($enfant->getUserId() !== $userId) {
                $error = 'Vous n\'êtes pas autorisé à supprimer cet enfant.';
            } else {
                // Vérifier si l'enfant est dans des trajets actifs
                if ($enfantRepository->isEnfantDansTrajetsActifs($enfantId)) {
                    $error = 'Impossible de supprimer cet enfant car il est inscrit dans des trajets actifs.';
                } else {
                    // Supprimer l'enfant
                    $entityManager->remove($enfant);
                    $entityManager->flush();
                    $success = 'Enfant supprimé avec succès !';
                    return $this->redirectToRoute('gestion_enfants');
                }
            }
        }

        // Gestion de l'ajout d'enfant
        if ($request->isMethod('POST') && $request->request->get('action') === 'ajouter_enfant') {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $dateNaissance = $request->request->get('dateNaissance');
            $sexe = $request->request->get('sexe');
            $ecoleId = $request->request->get('ecoleId');
            $certificatFile = $request->files->get('certificatScolarite');

            if ($nom && $prenom && $dateNaissance && $sexe && $ecoleId && $certificatFile) {
                // Vérifier que l'école existe et est validée via API
                $ecole = null;
                try {
                    $ecoleResp = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $ecoleId);
                    if ($ecoleResp->getStatusCode() === 200) {
                        $ecole = $ecoleResp->toArray();
                    }
                } catch (\Exception $e) {
                    $ecole = null;
                }
                if (!$ecole || !(isset($ecole['valide']) ? (bool)$ecole['valide'] : (($ecole['statut'] ?? null) === 'validee'))) {
                    $error = 'École invalide.';
                } else {
                    // Gérer l'upload du fichier
                    $originalFilename = pathinfo($certificatFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$certificatFile->guessExtension();

                    try {
                        $certificatFile->move(
                            $this->getParameter('certificats_directory'),
                            $newFilename
                        );

                        // Créer l'enfant
                        $enfant = new Enfant();
                        $enfant->setNom($nom);
                        $enfant->setPrenom($prenom);
                        $enfant->setDateNaissance(new \DateTime($dateNaissance));
                        $enfant->setSexe($sexe);
                        $enfant->setEcole($ecole['nom'] ?? '');
                        $enfant->setUserId($userId);
                        $enfant->setCertificatScolarite($newFilename);
                        $enfant->setValide(false); // En attente de validation admin

                        $entityManager->persist($enfant);
                        $entityManager->flush();

                        $success = 'Enfant ajouté avec succès ! Il sera validé par un administrateur.';
                        return $this->redirectToRoute('gestion_enfants');
                    } catch (\Exception $e) {
                        $error = 'Erreur lors de l\'ajout de l\'enfant: ' . $e->getMessage();
                    }
                }
            } else {
                $error = 'Tous les champs sont obligatoires';
            }
        }

        return $this->render('gestion/mes-enfants.html.twig', [
            'enfants' => $enfants,
            'ecoles' => $ecoles,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/mes-voitures', name: 'gestion_voitures', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function mesVoitures(Request $request, VoitureRepository $voitureRepository, EntityManagerInterface $entityManager): Response
    {
        $error = null;
        $success = null;
        
        $user = $this->getUser();
        $voitures = $voitureRepository->findBy(['userId' => $user->getId()]);

        // Gestion de l'ajout de voiture
        if ($request->isMethod('POST') && $request->request->get('action') === 'ajouter_voiture') {
            $marque = $request->request->get('marque');
            $modele = $request->request->get('modele');
            $couleur = $request->request->get('couleur');
            $immatriculation = $request->request->get('immatriculation');
            $nombrePlaces = (int) $request->request->get('nombrePlaces');

            if (!empty($marque) && !empty($modele) && !empty($couleur) && !empty($immatriculation) && $nombrePlaces > 0) {
                $voiture = new Voiture();
                $voiture->setMarque($marque);
                $voiture->setModele($modele);
                $voiture->setCouleur($couleur);
                $voiture->setImmatriculation($immatriculation);
                $voiture->setNombrePlaces($nombrePlaces);
                $voiture->setUserId($user->getId());

                $entityManager->persist($voiture);
                $entityManager->flush();

                $success = 'Voiture ajoutée avec succès !';
                return $this->redirectToRoute('gestion_voitures');
            } else {
                $error = 'Tous les champs sont obligatoires et le nombre de places doit être supérieur à 0';
            }
        }

        // Gestion de la suppression de voiture
        if ($request->isMethod('POST') && $request->request->get('action') === 'supprimer_voiture') {
            $voitureId = $request->request->get('voiture_id');
            $voiture = $voitureRepository->find($voitureId);
            
            if ($voiture && $voiture->getUserId() === $user->getId()) {
                // Vérifier si la voiture est utilisée dans des trajets actifs
                try {
                    $trajetsResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/voiture/' . $voitureId);
                    if ($trajetsResponse->getStatusCode() === 200) {
                        $trajets = $trajetsResponse->toArray();
                        $trajetsActifs = array_filter($trajets, function($trajet) {
                            return in_array($trajet['statut'], ['disponible', 'en_cours']);
                        });
                        
                        if (!empty($trajetsActifs)) {
                            $error = 'Impossible de supprimer cette voiture car elle est utilisée dans ' . count($trajetsActifs) . ' trajet(s) actif(s).';
                        } else {
                            $entityManager->remove($voiture);
                            $entityManager->flush();
                            $success = 'Voiture supprimée avec succès !';
                            return $this->redirectToRoute('gestion_voitures');
                        }
                    }
                } catch (\Exception $e) {
                    $error = 'Erreur lors de la vérification des trajets: ' . $e->getMessage();
                }
            } else {
                $error = 'Voiture non trouvée ou accès non autorisé';
            }
        }

        return $this->render('gestion/mes-voitures.html.twig', [
            'voitures' => $voitures,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/proposer-ecole', name: 'gestion_proposer_ecole', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function proposerEcole(Request $request): Response
    {
        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $adresse = $request->request->get('adresse');
            $ville = $request->request->get('ville');
            $codePostal = $request->request->get('codePostal');
            $telephone = $request->request->get('telephone');
            $email = $request->request->get('email');

            if ($nom && $adresse && $ville && $codePostal) {
                $data = [
                    'nom' => $nom,
                    'adresse' => $adresse,
                    'ville' => $ville,
                    'codePostal' => $codePostal,
                    'telephone' => $telephone,
                    'email' => $email,
                    'contributeurId' => $this->getUser()->getId(),
                    'statut' => 'en_attente'
                ];

                try {
                    $response = $this->httpClient->request('POST', $this->javaApiUrl . '/ecoles', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $data
                    ]);

                    if ($response->getStatusCode() === 201) {
                        $success = 'Votre proposition d\'école a été soumise avec succès ! Elle sera examinée par un administrateur.';
                        return $this->redirectToRoute('gestion_proposer_ecole');
                    } else {
                        $error = 'Erreur lors de la soumission de la proposition';
                    }
                } catch (\Exception $e) {
                    $error = 'Erreur lors de la soumission: ' . $e->getMessage();
                }
            } else {
                $error = 'Les champs nom, adresse, ville et code postal sont obligatoires';
            }
        }

        return $this->render('gestion/proposer-ecole.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/modifier-enfant/{id}', name: 'gestion_modifier_enfant', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function modifierEnfant(Request $request, int $id, EnfantRepository $enfantRepository, EntityManagerInterface $entityManager): Response
    {
        $error = null;
        $success = null;
        $enfant = null;
        $ecoles = [];

        $userId = $this->getUser()->getId();

        // Récupérer l'enfant
        $enfant = $enfantRepository->find($id);
        if (!$enfant || $enfant->getUserId() !== $userId) {
            return $this->redirectToRoute('gestion_enfants');
        }

        // Récupérer les écoles validées via l'API Java
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/validees');
            if ($response->getStatusCode() === 200) {
                $ecoles = $response->toArray();
            }
        } catch (\Exception $e) {
            $ecoles = [];
        }

        // Gestion de la modification
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $dateNaissance = $request->request->get('dateNaissance');
            $sexe = $request->request->get('sexe');
            $ecoleId = $request->request->get('ecoleId');

            if ($nom && $prenom && $dateNaissance && $sexe && $ecoleId) {
                // Vérifier que l'école existe et est validée via API
                $ecole = null;
                try {
                    $ecoleResp = $this->httpClient->request('GET', $this->javaApiUrl . '/ecoles/' . $ecoleId);
                    if ($ecoleResp->getStatusCode() === 200) {
                        $ecole = $ecoleResp->toArray();
                    }
                } catch (\Exception $e) {
                    $ecole = null;
                }
                if (!$ecole || !(isset($ecole['valide']) ? (bool)$ecole['valide'] : (($ecole['statut'] ?? null) === 'validee'))) {
                    $error = 'École invalide.';
                } else {
                    // Mettre à jour l'enfant
                    $enfant->setNom($nom);
                    $enfant->setPrenom($prenom);
                    $enfant->setDateNaissance(new \DateTime($dateNaissance));
                    $enfant->setSexe($sexe);
                    $enfant->setEcole($ecole['nom'] ?? '');

                    $entityManager->flush();
                    $success = 'Enfant modifié avec succès !';
                    return $this->redirectToRoute('gestion_enfants');
                }
            } else {
                $error = 'Tous les champs sont obligatoires';
            }
        }

        return $this->render('gestion/modifier-enfant.html.twig', [
            'enfant' => $enfant,
            'ecoles' => $ecoles,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/modifier-voiture/{id}', name: 'gestion_modifier_voiture', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function modifierVoiture(Request $request, int $id, VoitureRepository $voitureRepository, EntityManagerInterface $entityManager): Response
    {
        $error = null;
        $success = null;
        $user = $this->getUser();
        
        $voiture = $voitureRepository->find($id);
        
        if (!$voiture || $voiture->getUserId() !== $user->getId()) {
            return $this->redirectToRoute('gestion_voitures');
        }

        // Vérifier si la voiture est utilisée dans des trajets actifs
        $voitureUtilisee = false;
        try {
            $trajetsResponse = $this->httpClient->request('GET', $this->javaApiUrl . '/trajets/voiture/' . $id);
            if ($trajetsResponse->getStatusCode() === 200) {
                $trajets = $trajetsResponse->toArray();
                $now = new \DateTime('now');
                $trajetsActifs = array_filter($trajets, function($trajet) use ($now) {
                    $statut = $trajet['statut'] ?? '';
                    // Trajet en cours => toujours actif
                    if ($statut === 'en_cours') {
                        return true;
                    }
                    // Trajet disponible mais uniquement s'il n'est pas dans le passé
                    if ($statut === 'disponible') {
                        $dateDepart = $trajet['dateDepart'] ?? null;
                        $heureDepart = $trajet['heureDepart'] ?? null;
                        if ($dateDepart && $heureDepart) {
                            try {
                                $departDateTime = new \DateTime($dateDepart . ' ' . $heureDepart);
                                return $departDateTime >= $now;
                            } catch (\Exception $e) {
                                // En cas d'erreur de parsing, ne pas considérer actif
                                return false;
                            }
                        }
                    }
                    return false;
                });
                $voitureUtilisee = !empty($trajetsActifs);
            }
        } catch (\Exception $e) {
            // En cas d'erreur API, ne pas bloquer mais rester prudent (considérer non utilisée)
            $voitureUtilisee = false;
        }

        // Gestion de la modification
        if ($request->isMethod('POST')) {
            $marque = $request->request->get('marque');
            $modele = $request->request->get('modele');
            $couleur = $request->request->get('couleur');
            $immatriculation = $request->request->get('immatriculation');
            $nombrePlacesRaw = $request->request->get('nombrePlaces');
            $nombrePlaces = is_numeric($nombrePlacesRaw) ? (int) $nombrePlacesRaw : null;

            if (!empty($marque) && !empty($modele) && !empty($couleur) && !empty($immatriculation) && ($voitureUtilisee || ($nombrePlaces !== null && $nombrePlaces > 0))) {
                $voiture->setMarque($marque);
                $voiture->setModele($modele);
                $voiture->setCouleur($couleur);
                $voiture->setImmatriculation($immatriculation);
                // Ne pas permettre la modification du nombre de places si la voiture est utilisée dans des trajets actifs
                if (!$voitureUtilisee && $nombrePlaces !== null) {
                    $voiture->setNombrePlaces($nombrePlaces);
                }

                $entityManager->persist($voiture);
                $entityManager->flush();

                $success = 'Voiture modifiée avec succès !';
                if ($voitureUtilisee) {
                    $this->addFlash('warning', 'Le nombre de places n\'a pas pu être modifié car cette voiture est utilisée dans des trajets actifs.');
                }
                return $this->redirectToRoute('gestion_voitures');
            } else {
                $error = 'Tous les champs sont obligatoires et le nombre de places doit être supérieur à 0';
            }
        }

        return $this->render('gestion/modifier-voiture.html.twig', [
            'voiture' => $voiture,
            'voitureUtilisee' => $voitureUtilisee,
            'error' => $error,
            'success' => $success,
        ]);
    }
}