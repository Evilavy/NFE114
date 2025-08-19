<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class LoginRateLimiter
{
    private const MAX_ATTEMPTS = 5; // Maximum attempts before lockout
    private const LOCKOUT_DURATION = 900; // 15 minutes in seconds
    private const WINDOW_DURATION = 300; // 5 minutes window for attempts counting
    
    private CacheInterface $cache;
    
    public function __construct(?CacheInterface $cache = null)
    {
        // Use filesystem cache (PSR-6) by default, or injected cache for testing
        $this->cache = $cache ?? new FilesystemAdapter('login_attempts', 0, dirname(__DIR__, 2) . '/var/cache');
    }
    
    /**
     * Check if an IP is currently locked out
     */
    public function isLocked(string $ip): bool
    {
        $key = $this->getLockKey($ip);
        $item = $this->cache->getItem($key);
        $lockTime = $item->isHit() ? $item->get() : null;
        
        if ($lockTime === null) {
            return false;
        }
        
        // Check if lockout has expired
        if (time() >= $lockTime) {
            $this->cache->deleteItem($key);
            $this->resetAttempts($ip);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get remaining lockout time in seconds
     */
    public function getRemainingLockoutTime(string $ip): int
    {
        $key = $this->getLockKey($ip);
        $item = $this->cache->getItem($key);
        $lockTime = $item->isHit() ? $item->get() : null;
        
        if ($lockTime === null) {
            return 0;
        }
        
        $remaining = $lockTime - time();
        return max(0, $remaining);
    }
    
    /**
     * Record a failed login attempt
     */
    public function recordFailedAttempt(string $ip): void
    {
        if ($this->isLocked($ip)) {
            return;
        }
        
        $attemptsKey = $this->getAttemptsKey($ip);
        $attempts = $this->getAttempts($ip);
        
        // Add current timestamp to attempts
        $attempts[] = time();
        
        // Keep only attempts within the window
        $cutoff = time() - self::WINDOW_DURATION;
        $attempts = array_filter($attempts, fn($timestamp) => $timestamp > $cutoff);
        
        // Store updated attempts
        $attemptsItem = $this->cache->getItem($attemptsKey);
        $attemptsItem->set($attempts);
        $attemptsItem->expiresAfter(self::WINDOW_DURATION);
        $this->cache->save($attemptsItem);
        
        // Check if we should lock the IP
        if (count($attempts) >= self::MAX_ATTEMPTS) {
            $this->lockIp($ip);
        }
    }
    
    /**
     * Reset attempts for successful login
     */
    public function resetAttempts(string $ip): void
    {
        $this->cache->deleteItem($this->getAttemptsKey($ip));
        $this->cache->deleteItem($this->getLockKey($ip));
    }
    
    /**
     * Get current attempt count for an IP
     */
    public function getAttemptCount(string $ip): int
    {
        $attempts = $this->getAttempts($ip);
        $cutoff = time() - self::WINDOW_DURATION;
        $recentAttempts = array_filter($attempts, fn($timestamp) => $timestamp > $cutoff);
        
        return count($recentAttempts);
    }
    
    /**
     * Get remaining attempts before lockout
     */
    public function getRemainingAttempts(string $ip): int
    {
        return max(0, self::MAX_ATTEMPTS - $this->getAttemptCount($ip));
    }
    
    /**
     * Get the client IP from request
     */
    public function getClientIp(Request $request): string
    {
        // Check for various headers that might contain the real IP
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'HTTP_X_FORWARDED',          // Alternative proxy header
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'REMOTE_ADDR'                // Standard server variable
        ];
        
        foreach ($ipHeaders as $header) {
            $ip = $request->server->get($header);
            if (!empty($ip)) {
                // Take the first IP if multiple are provided
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to remote address
        return $request->getClientIp() ?? '127.0.0.1';
    }
    
    private function getAttempts(string $ip): array
    {
        $key = $this->getAttemptsKey($ip);
        $item = $this->cache->getItem($key);
        return $item->isHit() ? (array) $item->get() : [];
    }
    
    private function lockIp(string $ip): void
    {
        $key = $this->getLockKey($ip);
        $lockUntil = time() + self::LOCKOUT_DURATION;
        $item = $this->cache->getItem($key);
        $item->set($lockUntil);
        $item->expiresAfter(self::LOCKOUT_DURATION);
        $this->cache->save($item);
    }
    
    private function getAttemptsKey(string $ip): string
    {
        return 'login_attempts_' . hash('md5', $ip);
    }
    
    private function getLockKey(string $ip): string
    {
        return 'login_lock_' . hash('md5', $ip);
    }
    
    /**
     * Get configuration values for display
     */
    public function getConfig(): array
    {
        return [
            'max_attempts' => self::MAX_ATTEMPTS,
            'lockout_duration' => self::LOCKOUT_DURATION,
            'window_duration' => self::WINDOW_DURATION,
        ];
    }
    
    /**
     * Format remaining time for display
     */
    public function formatRemainingTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconde' . ($seconds > 1 ? 's' : '');
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        $result = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        if ($remainingSeconds > 0) {
            $result .= ' et ' . $remainingSeconds . ' seconde' . ($remainingSeconds > 1 ? 's' : '');
        }
        
        return $result;
    }
}
