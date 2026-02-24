<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Encryption extends BaseConfig
{
    /**
     * Encryption Algorithm
     * Supported: 'AES-128-CBC', 'AES-192-CBC', 'AES-256-CBC'
     */
    public $algorithm = 'AES-256-CBC';

    /**
     * Encryption key
     * Should be at least 32 characters for AES-256
     */
    public $encryptionKey = '';

    /**
     * Fields that should be automatically encrypted
     * Format: 'table_name' => ['field1', 'field2']
     */
    public $encryptedFields = [
        'personal_details' => [
            'passport_number_encrypted',
            'work_authorization_number_encrypted'
        ],
        'bank_details' => [
            'account_number_encrypted'
        ],
        'govt_ids' => [
            'id_number_encrypted'
        ],
        'health_records' => [
            'health_insurance_number_encrypted'
        ],
        'family_dependents' => [
            // Add encrypted fields if needed
        ]
    ];

    /**
     * Hash Fields - Used for searching without decryption
     * Format: 'table_name' => ['field_hash' => 'field_encrypted']
     */
    public $hashFields = [
        'bank_details' => [
            'account_number_hash' => 'account_number_encrypted'
        ],
        'govt_ids' => [
            'id_number_hash' => 'id_number_encrypted'
        ]
    ];

    /**
     * Fields that should be masked in responses (sensitive data)
     * Format: 'table_name' => ['field' => 'mask_type']
     * mask_type: 'full', 'bank_account', 'govt_id', 'email', 'phone'
     */
    public $maskedFields = [
        'personal_details' => [
            'passport_number_encrypted' => 'govt_id',
        ],
        'bank_details' => [
            'account_number_encrypted' => 'bank_account',
        ],
        'govt_ids' => [
            'id_number_encrypted' => 'govt_id'
        ],
        'health_records' => [
            'health_insurance_number_encrypted' => 'full'
        ]
    ];

    /**
     * Enable automatic encryption/decryption on model operations
     * If enabled, models will automatically handle encryption/decryption
     */
    public $autoEncrypt = true;

    /**
     * Enable field masking in responses
     * If enabled, sensitive fields will be masked instead of showing actual values
     */
    public $autoMask = true;

    /**
     * Log encryption/decryption operations
     */
    public $logOperations = true;

    public function __construct()
    {
        parent::__construct();
        
        // Load encryption key from environment variable
        $this->encryptionKey = getenv('encryption.key') ?: 
                               hash('sha256', getenv('app.baseURL'), true);
    }
}
