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
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

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

    public function authenticate(Request $request): Passport
    {
        $clientIp = $this->rateLimiter->getClientIp($request);
        
        // Check if IP is locked before attempting authentication
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

        // pré-remplir le formulaire de login si l’utilisateur se trompe de mot de passe
        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                $user = $this->userProvider->loadUserByIdentifier($userIdentifier);
                
                if (!$user instanceof JavaUser) {
                    throw new CustomUserMessageAuthenticationException('Utilisateur non trouvé.');
                }
                
                if (!$user->isApprovedByAdmin()) {
                    throw new CustomUserMessageAuthenticationException('Votre compte n\'a pas encore été approuvé par un administrateur.');
                }
                
                return $user;
            }),
            new PasswordCredentials($request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Reset failed attempts on successful login
        $clientIp = $this->rateLimiter->getClientIp($request);
        $this->rateLimiter->resetAttempts($clientIp);
        
        // Vérifie si l'utilisateur essayait d'accéder à une page avant de se connecter
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