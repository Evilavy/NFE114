<?php

namespace App\Security;

use App\Service\LoginRateLimiter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authentificateur principal - Gère le processus de connexion
 * 
 * Flow : Rate limiting → Récupération utilisateur → Vérification mot de passe → Redirection
 * Intègre la limitation de tentatives et la validation admin obligatoire.
 */
class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LoginRateLimiter $rateLimiter,
        private JavaUserProvider $userProvider
    ) {
    }

    /**
     * ÉTAPE 1 : Authentification - Vérification rate limiting puis récupération utilisateur
     * 
     * 1. Blocage IP si trop de tentatives échouées
     * 2. Récupération utilisateur depuis l'API Java
     * 3. Vérification validation admin obligatoire
     * 4. Vérification mot de passe (hashé ou en clair)
     */
    public function authenticate(Request $request): Passport
    {
        $clientIp = $this->rateLimiter->getClientIp($request);
        
        // ÉTAPE 1.1 : Vérification rate limiting avant toute tentative
        if ($this->rateLimiter->isLocked($clientIp)) {
            $remainingTime = $this->rateLimiter->formatRemainingTime(
                $this->rateLimiter->getRemainingLockoutTime($clientIp)
            );
            throw new CustomUserMessageAuthenticationException(
                "Trop de tentatives de connexion échouées. Votre IP est temporairement bloquée. Réessayez dans {$remainingTime}."
            );
        }
        
        // récupère la valeur du champ _username (email)
        $email = $request->request->get('_username', '');

        // pré-remplir le formulaire de login si l'utilisateur se trompe de mot de passe
        $request->getSession()->set('_security.last_username', $email);

        // ÉTAPE 1.2 : Création du passport avec badges de sécurité
        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                // Récupération utilisateur depuis l'API Java
                $user = $this->userProvider->loadUserByIdentifier($userIdentifier);
                
                if (!$user instanceof JavaUser) {
                    throw new CustomUserMessageAuthenticationException('Utilisateur non trouvé.');
                }
                
                // Vérification obligatoire : compte approuvé par admin
                if (!$user->isApprovedByAdmin()) {
                    throw new CustomUserMessageAuthenticationException('Votre compte n\'a pas encore été approuvé par un administrateur.');
                }
                
                return $user;
            }),
            new CustomCredentials(function($credentials, JavaUser $user) {
                $plainPassword = $credentials;
                $hashedPassword = $user->getPassword();
                
                // ÉTAPE 1.3 : Vérification mot de passe (compatible hashé et en clair)
                if (strpos($hashedPassword, '$2a$') === 0 || strpos($hashedPassword, '$2y$') === 0) {
                    // Mot de passe hashé BCrypt → password_verify()
                    return password_verify($plainPassword, $hashedPassword);
                } else {
                    // Mot de passe en clair (ancien format) → comparaison directe
                    return $plainPassword === $hashedPassword;
                }
            }, $request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * ÉTAPE 2 : Succès d'authentification - Reset rate limiting et redirection
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Reset du compteur de tentatives échouées
        $clientIp = $this->rateLimiter->getClientIp($request);
        $this->rateLimiter->resetAttempts($clientIp);
        
        // Redirection intelligente : page demandée ou accueil
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
} 