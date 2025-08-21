<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Provider utilisateur - Récupère les données depuis l'API Java
 * 
 * Charge les utilisateurs depuis l'API Java et les convertit en JavaUser Symfony.
 * Gère le refresh automatique des données utilisateur.
 */
class JavaUserProvider implements UserProviderInterface
{
    private string $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * Charge un utilisateur par son email depuis l'API Java
     * 
     * Récupère tous les utilisateurs puis filtre par email.
     * Retourne un JavaUser ou lance une exception si non trouvé.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            // Récupération de tous les utilisateurs depuis l'API Java
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/users');
            $users = $response->toArray();
            
            // Recherche par email (identifier)
            foreach ($users as $userData) {
                if ($userData['email'] === $identifier) {
                    return new JavaUser($userData);
                }
            }
            
            throw new UserNotFoundException(sprintf('User with email "%s" not found.', $identifier));
        } catch (\Exception $e) {
            throw new UserNotFoundException('Unable to load user from API.', 0, $e);
        }
    }

    /**
     * Refresh utilisateur - Recharge les données depuis l'API Java
     * 
     * Permet de synchroniser les données utilisateur avec l'API Java
     * (points, validation admin, etc.)
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        // Rechargement depuis l'API Java pour synchroniser les données
        if ($user instanceof JavaUser) {
            return $this->loadUserByIdentifier($user->getUserIdentifier());
        }
        
        throw new \InvalidArgumentException('Unsupported user class: ' . get_class($user));
    }

    public function supportsClass(string $class): bool
    {
        return JavaUser::class === $class || is_subclass_of($class, JavaUser::class);
    }
}
