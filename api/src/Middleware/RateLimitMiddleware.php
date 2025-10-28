<?php

// api/src/Middleware/RateLimitMiddleware.php - Rate Limiting

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Database;
use App\Exceptions\RateLimitException;

class RateLimitMiddleware
{
    private Database $db;
    private int $minuteLimit;
    private int $hourLimit;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->minuteLimit = (int)($_ENV['API_RATE_LIMIT_PER_MINUTE'] ?? 60);
        $this->hourLimit = (int)($_ENV['API_RATE_LIMIT_PER_HOUR'] ?? 3600);
    }

    public function process(Request $request): Request
    {
        $identifier = $this->getIdentifier($request);
        $endpoint = $request->getPath();

        // Check minute limit
        if (!$this->checkLimit($identifier, $endpoint, 'minute', $this->minuteLimit)) {
            throw new RateLimitException('Rate limit exceeded for this minute');
        }

        // Check hour limit
        if (!$this->checkLimit($identifier, $endpoint, 'hour', $this->hourLimit)) {
            throw new RateLimitException('Rate limit exceeded for this hour');
        }

        // Record the request
        $this->recordRequest($identifier, $endpoint);

        return $request;
    }

    private function getIdentifier(Request $request): string
    {
        // Use API key if present, otherwise use IP
        $apiKey = $request->getHeader('x-api-key');
        return $apiKey ?: $request->getIp();
    }

    private function checkLimit(string $identifier, string $endpoint, string $window, int $limit): bool
    {
        $windowStart = $this->getWindowStart($window);
        
        $sql = "SELECT requests_count FROM rate_limits 
                WHERE identifier = ? AND endpoint = ? AND window_start = ?";
        
        $result = $this->db->fetch($sql, [$identifier, $endpoint, $windowStart]);
        
        return !$result || $result['requests_count'] < $limit;
    }

    private function recordRequest(string $identifier, string $endpoint): void
    {
        $minuteStart = $this->getWindowStart('minute');
        $hourStart = $this->getWindowStart('hour');

        // Record minute window
        $this->incrementCounter($identifier, $endpoint, $minuteStart);
        
        // Record hour window (different endpoint key)
        $this->incrementCounter($identifier, $endpoint . '_hour', $hourStart);
    }

    private function incrementCounter(string $identifier, string $endpoint, string $windowStart): void
    {
        $sql = "INSERT INTO rate_limits (identifier, endpoint, requests_count, window_start, created_at, updated_at) 
                VALUES (?, ?, 1, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                requests_count = requests_count + 1, updated_at = NOW()";
        
        $this->db->execute($sql, [$identifier, $endpoint, $windowStart]);
    }

    private function getWindowStart(string $window): string
    {
        $now = new \DateTime();
        
        if ($window === 'minute') {
            $now->setTime((int)$now->format('H'), (int)$now->format('i'), 0);
        } else { // hour
            $now->setTime((int)$now->format('H'), 0, 0);
        }
        
        return $now->format('Y-m-d H:i:s');
    }
}