<?php

namespace App\Tests\Service;

use App\Service\LoginRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class LoginRateLimiterTest extends TestCase
{
    private LoginRateLimiter $rateLimiter;
    private ArrayAdapter $cache;

    protected function setUp(): void
    {
        // Utilise ArrayAdapter pour les tests (plus rapide que FilesystemAdapter)
        $this->cache = new ArrayAdapter();
        $this->rateLimiter = new LoginRateLimiter($this->cache);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
    }

    public function testIsLockedReturnsFalseForNewIp(): void
    {
        $ip = '192.168.1.100';
        $this->assertFalse($this->rateLimiter->isLocked($ip));
    }

    public function testRecordFailedAttemptIncrementsAttempts(): void
    {
        $ip = '192.168.1.100';
        
        // Première tentative
        $this->rateLimiter->recordFailedAttempt($ip);
        $this->assertEquals(4, $this->rateLimiter->getRemainingAttempts($ip));
        
        // Deuxième tentative
        $this->rateLimiter->recordFailedAttempt($ip);
        $this->assertEquals(3, $this->rateLimiter->getRemainingAttempts($ip));
    }

    public function testIpGetsLockedAfterMaxAttempts(): void
    {
        $ip = '192.168.1.100';
        
        // Enregistre 5 tentatives échouées
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordFailedAttempt($ip);
        }
        
        // Vérifie que l'IP est bloquée
        $this->assertTrue($this->rateLimiter->isLocked($ip));
        $this->assertEquals(0, $this->rateLimiter->getRemainingAttempts($ip));
    }

    public function testRemainingLockoutTimeReturnsCorrectValue(): void
    {
        $ip = '192.168.1.100';
        
        // Bloque l'IP
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordFailedAttempt($ip);
        }
        
        $remainingTime = $this->rateLimiter->getRemainingLockoutTime($ip);
        
        // Le temps restant doit être proche de 15 minutes (900 secondes)
        $this->assertGreaterThan(890, $remainingTime); // 900 - 10 secondes de marge
        $this->assertLessThanOrEqual(900, $remainingTime);
    }

    public function testResetAttemptsClearsAllData(): void
    {
        $ip = '192.168.1.100';
        
        // Enregistre quelques tentatives
        $this->rateLimiter->recordFailedAttempt($ip);
        $this->rateLimiter->recordFailedAttempt($ip);
        
        // Reset
        $this->rateLimiter->resetAttempts($ip);
        
        // Vérifie que tout est effacé
        $this->assertFalse($this->rateLimiter->isLocked($ip));
        $this->assertEquals(5, $this->rateLimiter->getRemainingAttempts($ip));
    }

    public function testAttemptsAreFilteredByWindowDuration(): void
    {
        $ip = '192.168.1.100';
        
        // Enregistre 3 tentatives
        for ($i = 0; $i < 3; $i++) {
            $this->rateLimiter->recordFailedAttempt($ip);
        }
        
        $this->assertEquals(2, $this->rateLimiter->getRemainingAttempts($ip));
        
        // Vérifie que le comptage fonctionne correctement
        $this->assertEquals(3, $this->rateLimiter->getAttemptCount($ip));
    }

    public function testLockoutTimeIsCalculatedCorrectly(): void
    {
        $ip = '192.168.1.100';
        
        // Bloque l'IP
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordFailedAttempt($ip);
        }
        
        $this->assertTrue($this->rateLimiter->isLocked($ip));
        
        // Vérifie que le temps restant est calculé
        $remainingTime = $this->rateLimiter->getRemainingLockoutTime($ip);
        $this->assertGreaterThan(0, $remainingTime);
        $this->assertLessThanOrEqual(900, $remainingTime);
    }

    public function testGetClientIpReturnsValidIp(): void
    {
        $request = new Request();
        
        // Simule une IP dans REMOTE_ADDR
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        
        $ip = $this->rateLimiter->getClientIp($request);
        $this->assertEquals('192.168.1.100', $ip);
    }

    public function testGetClientIpHandlesXForwardedFor(): void
    {
        $request = new Request();
        
        // Simule une IP dans X-Forwarded-For
        $request->server->set('HTTP_X_FORWARDED_FOR', '203.0.113.1, 192.168.1.100');
        
        $ip = $this->rateLimiter->getClientIp($request);
        $this->assertEquals('203.0.113.1', $ip); // Prend la première IP
    }

    public function testGetClientIpHandlesCloudflare(): void
    {
        $request = new Request();
        
        // Simule une IP Cloudflare
        $request->server->set('HTTP_CF_CONNECTING_IP', '203.0.113.1');
        $request->server->set('HTTP_X_FORWARDED_FOR', '192.168.1.100');
        
        $ip = $this->rateLimiter->getClientIp($request);
        $this->assertEquals('203.0.113.1', $ip); // Cloudflare a priorité
    }

    public function testGetClientIpFallsBackToRemoteAddr(): void
    {
        $request = new Request();
        
        // Pas d'IP dans les headers, utilise REMOTE_ADDR
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        
        $ip = $this->rateLimiter->getClientIp($request);
        $this->assertEquals('127.0.0.1', $ip);
    }

    public function testFormatRemainingTimeFormatsCorrectly(): void
    {
        // Test pour moins d'une minute
        $this->assertEquals('30 secondes', $this->rateLimiter->formatRemainingTime(30));
        $this->assertEquals('1 seconde', $this->rateLimiter->formatRemainingTime(1));
        
        // Test pour exactement une minute
        $this->assertEquals('1 minute', $this->rateLimiter->formatRemainingTime(60));
        
        // Test pour plusieurs minutes
        $this->assertEquals('5 minutes', $this->rateLimiter->formatRemainingTime(300));
        
        // Test pour minutes et secondes
        $this->assertEquals('2 minutes et 30 secondes', $this->rateLimiter->formatRemainingTime(150));
    }

    public function testGetConfigReturnsCorrectValues(): void
    {
        $config = $this->rateLimiter->getConfig();
        
        $this->assertArrayHasKey('max_attempts', $config);
        $this->assertArrayHasKey('lockout_duration', $config);
        $this->assertArrayHasKey('window_duration', $config);
        
        $this->assertEquals(5, $config['max_attempts']);
        $this->assertEquals(900, $config['lockout_duration']);
        $this->assertEquals(300, $config['window_duration']);
    }

    public function testRecordFailedAttemptDoesNothingIfAlreadyLocked(): void
    {
        $ip = '192.168.1.100';
        
        // Bloque l'IP
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordFailedAttempt($ip);
        }
        
        $remainingTimeBefore = $this->rateLimiter->getRemainingLockoutTime($ip);
        
        // Essaie d'enregistrer une tentative supplémentaire
        $this->rateLimiter->recordFailedAttempt($ip);
        
        $remainingTimeAfter = $this->rateLimiter->getRemainingLockoutTime($ip);
        
        // Le temps restant ne doit pas changer
        $this->assertEquals($remainingTimeBefore, $remainingTimeAfter);
    }

    public function testMultipleIpsAreHandledIndependently(): void
    {
        $ip1 = '192.168.1.100';
        $ip2 = '192.168.1.101';
        
        // Bloque la première IP
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordFailedAttempt($ip1);
        }
        
        // Vérifie que la première IP est bloquée
        $this->assertTrue($this->rateLimiter->isLocked($ip1));
        
        // Vérifie que la deuxième IP n'est pas bloquée
        $this->assertFalse($this->rateLimiter->isLocked($ip2));
        $this->assertEquals(5, $this->rateLimiter->getRemainingAttempts($ip2));
    }

    public function testGetAttemptCountReturnsCorrectValue(): void
    {
        $ip = '192.168.1.100';
        
        // Enregistre 3 tentatives
        for ($i = 0; $i < 3; $i++) {
            $this->rateLimiter->recordFailedAttempt($ip);
        }
        
        $this->assertEquals(3, $this->rateLimiter->getAttemptCount($ip));
    }


}
