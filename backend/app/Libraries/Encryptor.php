<?php

namespace App\Libraries;

use Exception;

class Encryptor
{
    private $algorithm = 'AES-256-CBC';
    private $encryptionKey;

    public function __construct()
    {
        // Get encryption key from environment or use default
        $this->encryptionKey = getenv('encryption.key') ?: 
                               hash('sha256', getenv('app.baseURL'), true);
    }

    /**
     * Encrypt data using AES-256-CBC
     */
    public function encrypt($data)
    {
        try {
            if (empty($data)) {
                return null;
            }

            // Generate a random IV
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->algorithm));

            // Encrypt the data
            $encrypted = openssl_encrypt(
                $data,
                $this->algorithm,
                $this->encryptionKey,
                0,
                $iv
            );

            if ($encrypted === false) {
                throw new Exception('Encryption failed');
            }

            // Combine IV and encrypted data, then base64 encode
            $combined = base64_encode($iv . $encrypted);

            return $combined;
        } catch (Exception $e) {
            log_message('error', 'Encryption Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Decrypt data using AES-256-CBC
     */
    public function decrypt($encryptedData)
    {
        try {
            if (empty($encryptedData)) {
                return null;
            }

            // Decode from base64
            $data = base64_decode($encryptedData);

            if ($data === false) {
                throw new Exception('Failed to decode base64 data');
            }

            // Extract IV and encrypted data
            $ivLength = openssl_cipher_iv_length($this->algorithm);
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);

            // Decrypt the data
            $decrypted = openssl_decrypt(
                $encrypted,
                $this->algorithm,
                $this->encryptionKey,
                0,
                $iv
            );

            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }

            return $decrypted;
        } catch (Exception $e) {
            log_message('error', 'Decryption Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hash a field for uniqueness checks (one-way)
     */
    public function hashField($data)
    {
        return hash('sha256', $data . $this->encryptionKey);
    }

    /**
     * Mask sensitive data for display
     * @param string $data Original data
     * @param string $type Type of data (bank, govt_id, etc.)
     */
    public function maskSensitiveData($data, $type = 'generic')
    {
        if (empty($data)) {
            return null;
        }

        switch ($type) {
            case 'bank_account':
                // Show only last 4 digits
                $length = strlen($data);
                return str_repeat('*', $length - 4) . substr($data, -4);

            case 'govt_id':
                // Show only first 2 and last 2 characters
                $length = strlen($data);
                if ($length <= 4) {
                    return str_repeat('*', $length);
                }
                return substr($data, 0, 2) . str_repeat('*', $length - 4) . substr($data, -2);

            case 'email':
                // Show only first character and domain
                $parts = explode('@', $data);
                if (count($parts) == 2) {
                    return $parts[0][0] . str_repeat('*', strlen($parts[0]) - 1) . '@' . $parts[1];
                }
                return str_repeat('*', strlen($data));

            case 'phone':
                // Show only last 4 digits
                $length = strlen($data);
                return str_repeat('*', $length - 4) . substr($data, -4);

            default:
                // Generic masking - show 30%
                $length = strlen($data);
                $showLength = max(1, (int)($length * 0.3));
                return substr($data, 0, $showLength) . str_repeat('*', $length - $showLength);
        }
    }

    /**
     * Check if data is encrypted (has base64 marker)
     */
    public function isEncrypted($data)
    {
        if (empty($data)) {
            return false;
        }

        // Try to decode as base64
        $decoded = base64_decode($data, true);
        return $decoded !== false && strlen($decoded) > openssl_cipher_iv_length($this->algorithm);
    }
}
