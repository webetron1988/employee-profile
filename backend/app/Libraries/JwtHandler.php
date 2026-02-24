<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtHandler
{
    private $publicKey;
    private $privateKey;
    private $algorithm = 'RS256';
    private $tokenExpiry = 3600; // 1 hour
    private $refreshTokenExpiry = 604800; // 7 days

    public function __construct()
    {
        $this->publicKey = getenv('jwt.public_key') ?: file_get_contents(APPPATH . '../config/keys/public.pem');
        $this->privateKey = getenv('jwt.private_key') ?: file_get_contents(APPPATH . '../config/keys/private.pem');
    }

    /**
     * Generate JWT token
     */
    public function generateToken($data, $expirySeconds = null)
    {
        try {
            $now = time();
            $expiry = $expirySeconds ?? $this->tokenExpiry;

            $payload = [
                'iat' => $now,
                'exp' => $now + $expiry,
                'iss' => getenv('app.baseURL'),
                'data' => $data,
            ];

            $token = JWT::encode($payload, $this->privateKey, $this->algorithm);
            return [
                'status' => true,
                'token' => $token,
                'expires_in' => $expiry,
                'type' => 'Bearer',
            ];
        } catch (Exception $e) {
            log_message('error', 'JWT Token Generation Error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to generate token',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate JWT token
     */
    public function validateToken($token)
    {
        try {
            // Remove Bearer prefix if present
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }

            $decoded = JWT::decode($token, new Key($this->publicKey, $this->algorithm));

            return [
                'status' => true,
                'claims' => (array) $decoded,
                'data' => (array) $decoded->data,
            ];
        } catch (\Firebase\JWT\ExpiredException $e) {
            log_message('info', 'JWT Token Expired: ' . $e->getMessage());
            return [
                'status' => false,
                'reason' => 'token_expired',
                'message' => 'Token has expired',
            ];
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            log_message('warning', 'JWT Signature Invalid: ' . $e->getMessage());
            return [
                'status' => false,
                'reason' => 'invalid_signature',
                'message' => 'Invalid token signature',
            ];
        } catch (Exception $e) {
            log_message('error', 'JWT Validation Error: ' . $e->getMessage());
            return [
                'status' => false,
                'reason' => 'invalid_token',
                'message' => 'Invalid token',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken($token)
    {
        $validation = $this->validateToken($token);

        if (!$validation['status']) {
            return $validation;
        }

        // Generate new token with same data
        $newToken = $this->generateToken($validation['data']);

        return $newToken;
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken($data)
    {
        return $this->generateToken($data, $this->refreshTokenExpiry);
    }

    /**
     * Extract claims from token
     */
    public function extractClaims($token)
    {
        $validation = $this->validateToken($token);

        if (!$validation['status']) {
            return null;
        }

        return $validation['claims'];
    }

    /**
     * Check if token is about to expire (within 5 minutes)
     */
    public function isTokenExpiringSoon($token)
    {
        $claims = $this->extractClaims($token);

        if (!$claims) {
            return true; // Consider as soon to expire if invalid
        }

        $timeLeft = $claims['exp'] - time();
        return $timeLeft < 300; // 5 minutes
    }
}
