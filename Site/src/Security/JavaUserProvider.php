<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JavaUserProvider implements UserProviderInterface
{
    private string $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/users');
            $users = $response->toArray();
            
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

    public function refreshUser(UserInterface $user): UserInterface
    {
        // Pour les utilisateurs JavaUser, on recharge depuis l'API
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
