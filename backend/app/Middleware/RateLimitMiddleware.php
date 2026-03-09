<?php

namespace App\Middleware;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitMiddleware implements FilterInterface
{
    // Default: 60 requests per minute per IP
    private int $maxRequests = 60;
    private int $windowSeconds = 60;

    public function before(RequestInterface $request, $arguments = null)
    {
        // Use arguments to override defaults: ['100', '60'] = 100 req / 60 sec
        if (!empty($arguments[0])) {
            $this->maxRequests = (int) $arguments[0];
        }
        if (!empty($arguments[1])) {
            $this->windowSeconds = (int) $arguments[1];
        }

        $ip = $request->getIPAddress();
        $path = $request->getPath();
        $key = 'rate_limit_' . md5($ip . '_' . $path);

        $cache = \Config\Services::cache();
        $entry = $cache->get($key);

        if ($entry === null) {
            $cache->save($key, ['count' => 1, 'start' => time()], $this->windowSeconds);
            return null;
        }

        $elapsed = time() - $entry['start'];

        // Window expired — reset
        if ($elapsed >= $this->windowSeconds) {
            $cache->save($key, ['count' => 1, 'start' => time()], $this->windowSeconds);
            return null;
        }

        // Within window — check count
        if ($entry['count'] >= $this->maxRequests) {
            $retryAfter = $this->windowSeconds - $elapsed;
            return response()
                ->setStatusCode(429)
                ->setContentType('application/json')
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setBody(json_encode([
                    'status' => 'error',
                    'message' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.',
                    'code' => 'RATE_LIMITED',
                ]));
        }

        // Increment counter
        $entry['count']++;
        $remaining = $this->windowSeconds - $elapsed;
        $cache->save($key, $entry, $remaining > 0 ? $remaining : 1);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
