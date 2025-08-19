<?php

namespace App\Controller;

use App\Service\LoginRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, LoginRateLimiter $rateLimiter): Response
    {
        $clientIp = $rateLimiter->getClientIp($request);
        
        // Check if IP is currently locked
        $isLocked = $rateLimiter->isLocked($clientIp);
        $remainingLockoutTime = 0;
        $remainingAttempts = $rateLimiter->getRemainingAttempts($clientIp);
        
        if ($isLocked) {
            $remainingLockoutTime = $rateLimiter->getRemainingLockoutTime($clientIp);
        }
        
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // If there was an authentication error and IP is not locked, record failed attempt
        if ($error && !$isLocked) {
            $rateLimiter->recordFailedAttempt($clientIp);
            
            // Refresh lockout status after recording attempt
            $isLocked = $rateLimiter->isLocked($clientIp);
            if ($isLocked) {
                $remainingLockoutTime = $rateLimiter->getRemainingLockoutTime($clientIp);
            }
            $remainingAttempts = $rateLimiter->getRemainingAttempts($clientIp);
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'is_locked' => $isLocked,
            'remaining_lockout_time' => $remainingLockoutTime,
            'remaining_attempts' => $remainingAttempts,
            'rate_limiter_config' => $rateLimiter->getConfig(),
            'formatted_remaining_time' => $isLocked ? $rateLimiter->formatRemainingTime($remainingLockoutTime) : null,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
