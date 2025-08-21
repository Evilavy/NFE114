<?php

namespace App\Controller;

use App\Form\RegistrationForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Contrôleur Inscription
 *
 * Valide le formulaire et crée l'utilisateur via l'API Java.
 */
class RegistrationController extends AbstractController
{
    private string $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * Inscription d'un nouvel utilisateur et redirection vers la connexion.
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(RegistrationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    /** @var string $plainPassword */
                    $plainPassword = $form->get('plainPassword')->getData();

                    // Debug: Afficher le mot de passe en clair
                    error_log("Plain password: " . $plainPassword);

                    // Hash le mot de passe directement avec password_hash()
                    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

                    // Préparer les données utilisateur pour l'API Java
                    $userData = [
                        'nom' => $form->get('nom')->getData(),
                        'prenom' => $form->get('prenom')->getData(),
                        'email' => $form->get('email')->getData(),
                        'password' => $hashedPassword,
                        'role' => $form->get('role')->getData()
                    ];

                    // Debug: Afficher le mot de passe hashé
                    error_log("Hashed password: " . $userData['password']);

                    // Gérer le rôle "autre"
                    $role = $form->get('role')->getData();
                    $roleAutre = $form->get('roleAutre')->getData();
                    
                    if ($role === 'autre' && !empty($roleAutre)) {
                        $userData['roleAutre'] = $roleAutre;
                    }

                    // Debug: Afficher toutes les données envoyées
                    error_log("User data sent to Java API: " . json_encode($userData));

                    // Créer l'utilisateur via l'API Java
                    $response = $this->httpClient->request('POST', $this->javaApiUrl . '/users', [
                        'json' => $userData
                    ]);

                    if ($response->getStatusCode() === 201) {
                        // Rediriger vers la page de connexion avec un message de session temporaire
                        $this->addFlash('info', 'Votre inscription a été enregistrée. Elle sera validée par un administrateur dans les plus brefs délais.');
                        return $this->redirectToRoute('app_login');
                    } else {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'inscription. Veuillez réessayer.');
                    }
                } catch (\Exception $e) {
                    // Debug: Afficher l'erreur complète
                    error_log("Registration error: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    $this->addFlash('error', 'Une erreur s\'est produite lors de l\'inscription. Veuillez réessayer.');
                }
            } else {
                // Afficher les erreurs de validation
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
