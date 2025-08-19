<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class JavaUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private array $userData;

    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    public function getUserIdentifier(): string
    {
        return $this->userData['email'];
    }

    public function getRoles(): array
    {
        // Définir les rôles basés sur les données Java
        $roles = ['ROLE_USER'];
        
        // Si c'est un admin
        if ($this->userData['email'] === 'admin@example.com') {
            $roles[] = 'ROLE_ADMIN';
        }
        
        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->userData['password'];
    }

    public function eraseCredentials(): void
    {
        // Si nécessaire, effacer les données sensibles
    }

    // Getters pour accéder aux données utilisateur
    public function getId(): int
    {
        return $this->userData['id'];
    }

    public function getEmail(): string
    {
        return $this->userData['email'];
    }

    public function getNom(): string
    {
        return $this->userData['nom'];
    }

    public function getPrenom(): string
    {
        return $this->userData['prenom'];
    }

    public function getPoints(): int
    {
        return $this->userData['points'];
    }

    public function getRole(): string
    {
        return $this->userData['role'];
    }

    public function isApprovedByAdmin(): bool
    {
        return $this->userData['approuveParAdmin'];
    }

    public function getDateCreation(): string
    {
        return $this->userData['dateCreation'];
    }

    // Méthode pour obtenir toutes les données
    public function getUserData(): array
    {
        return $this->userData;
    }
}
