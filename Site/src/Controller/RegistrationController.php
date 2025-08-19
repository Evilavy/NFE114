<?php

namespace App\Controller;

use App\Form\RegistrationForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationController extends AbstractController
{
    private string $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

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

                    // Préparer les données utilisateur pour l'API Java
                    $userData = [
                        'nom' => $form->get('nom')->getData(),
                        'prenom' => $form->get('prenom')->getData(),
                        'email' => $form->get('email')->getData(),
                        'password' => $userPasswordHasher->hashPassword(
                            new \App\Security\JavaUser(['password' => $plainPassword]), 
                            $plainPassword
                        ),
                        'role' => $form->get('role')->getData()
                    ];

                    // Gérer le rôle "autre"
                    $role = $form->get('role')->getData();
                    $roleAutre = $form->get('roleAutre')->getData();
                    
                    if ($role === 'autre' && !empty($roleAutre)) {
                        $userData['roleAutre'] = $roleAutre;
                    }

                    // Créer l'utilisateur via l'API Java
                    $response = $this->httpClient->request('POST', $this->javaApiUrl . '/users', [
                        'json' => $userData
                    ]);

                    if ($response->getStatusCode() === 201) {
                        $this->addFlash('success', 'Votre inscription a été enregistrée. Elle sera validée par un administrateur dans les plus brefs délais.');
                        return $this->redirectToRoute('app_login');
                    } else {
                        $this->addFlash('error', 'Une erreur s\'est produite lors de l\'inscription. Veuillez réessayer.');
                    }
                } catch (\Exception $e) {
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
