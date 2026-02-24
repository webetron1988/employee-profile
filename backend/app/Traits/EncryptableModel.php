<?php

namespace App\Traits;

use Config\Encryption as EncryptionConfig;

trait EncryptableModel
{
    /**
     * List of fields that should be encrypted (without _encrypted suffix)
     * Override this in models that use this trait
     *
     * @var array
     */
    protected $encryptedFields = [];

    /**
     * Encryptor service instance
     */
    protected $encryptor;

    /**
     * Encryption config instance
     */
    protected $encryptionConfig;

    /**
     * Initialize encryption handling
     */
    private function initializeEncryption()
    {
        if (!$this->encryptor) {
            $this->encryptor = service('encryptor');
            $this->encryptionConfig = config('Encryption');
        }
    }

    /**
     * Encrypt data before saving to database
     * Called automatically before insert/update
     *
     * @param array $data Data to be saved
     * @return array
     */
    protected function encryptSensitiveData($data)
    {
        $this->initializeEncryption();

        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            // Check if this field should be encrypted
            if ($this->shouldEncryptField($key)) {
                // Get the corresponding encrypted field name
                $encryptedField = $this->getEncryptedFieldName($key);

                if (isset($data[$key])) {
                    // Encrypt the value
                    $encrypted = $this->encryptor->encrypt($data[$key]);
                    $data[$encryptedField] = $encrypted;

                    // Generate hash for searchability (if applicable)
                    $hashField = $this->getHashFieldName($encryptedField);
                    if ($hashField) {
                        $data[$hashField] = hash('sha256', $value);
                    }

                    // Remove plaintext field from data
                    unset($data[$key]);

                    // Log the operation
                    if ($this->encryptionConfig->logOperations) {
                        log_message('debug', "Encrypted field: $key -> $encryptedField");
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Decrypt data after retrieving from database
     * Called automatically after find/findAll
     *
     * @param array|object $data Retrieved data
     * @return array|object
     */
    protected function decryptSensitiveData($data)
    {
        $this->initializeEncryption();

        if (!is_array($data) && !is_object($data)) {
            return $data;
        }

        // Convert to array if object
        $isObject = is_object($data);
        $dataArray = (array)$data;

        foreach ($dataArray as $key => $value) {
            // Check if this is an encrypted field
            if ($this->isEncryptedField($key)) {
                if (!empty($value)) {
                    // Decrypt the value
                    $decrypted = $this->encryptor->decrypt($value);

                    if ($decrypted !== null) {
                        $dataArray[$key] = $decrypted;

                        // Log the operation
                        if ($this->encryptionConfig->logOperations) {
                            log_message('debug', "Decrypted field: $key");
                        }
                    }
                }
            }
        }

        // Convert back to object if needed
        return $isObject ? (object)$dataArray : $dataArray;
    }

    /**
     * Mask sensitive data for display in responses
     *
     * @param array|object $data Data to mask
     * @return array|object
     */
    protected function maskSensitiveData($data)
    {
        $this->initializeEncryption();

        if (!is_array($data) && !is_object($data)) {
            return $data;
        }

        // Convert to array if object
        $isObject = is_object($data);
        $dataArray = (array)$data;

        $tableMasks = $this->encryptionConfig->maskedFields[$this->table] ?? [];

        foreach ($tableMasks as $field => $maskType) {
            if (isset($dataArray[$field])) {
                $dataArray[$field] = $this->encryptor->maskSensitiveData(
                    $dataArray[$field],
                    $maskType
                );
            }
        }

        // Convert back to object if needed
        return $isObject ? (object)$dataArray : $dataArray;
    }

    /**
     * Check if a field should be encrypted (plain text version)
     *
     * @param string $field Field name
     * @return bool
     */
    private function shouldEncryptField($field)
    {
        // Check if field matches pattern: field -> field_encrypted
        $encryptedField = $field . '_encrypted';
        return in_array($encryptedField, $this->allowedFields);
    }

    /**
     * Check if a field is encrypted (encrypted version)
     *
     * @param string $field Field name (e.g., account_number_encrypted)
     * @return bool
     */
    private function isEncryptedField($field)
    {
        $tableEncrypted = $this->encryptionConfig->encryptedFields[$this->table] ?? [];
        return in_array($field, $tableEncrypted);
    }

    /**
     * Get the encrypted field name from plain text field name
     *
     * @param string $field Plain text field (e.g., account_number)
     * @return string Encrypted field name (e.g., account_number_encrypted)
     */
    private function getEncryptedFieldName($field)
    {
        return $field . '_encrypted';
    }

    /**
     * Get the hash field name for an encrypted field
     *
     * @param string $encryptedField Encrypted field name
     * @return string|null Hash field name or null
     */
    private function getHashFieldName($encryptedField)
    {
        $tableHashes = $this->encryptionConfig->hashFields[$this->table] ?? [];

        foreach ($tableHashes as $hashField => $encField) {
            if ($encField === $encryptedField) {
                return $hashField;
            }
        }

        return null;
    }

    /**
     * Override beforeInsert to encrypt data before save
     */
    protected function beforeInsert(array $data)
    {
        if ($this->encryptionConfig->autoEncrypt) {
            $data['data'] = $this->encryptSensitiveData($data['data']);
        }

        return parent::beforeInsert($data);
    }

    /**
     * Override beforeUpdate to encrypt data before save
     */
    protected function beforeUpdate(array $data)
    {
        if ($this->encryptionConfig->autoEncrypt) {
            $data['data'] = $this->encryptSensitiveData($data['data']);
        }

        return parent::beforeUpdate($data);
    }

    /**
     * Override find to decrypt data after retrieval
     */
    public function find($id = null)
    {
        $result = parent::find($id);

        if ($result && $this->encryptionConfig->autoEncrypt) {
            $result = $this->decryptSensitiveData($result);
        }

        return $result;
    }

    /**
     * Override findAll to decrypt data after retrieval
     */
    public function findAll($limit = 0, $offset = 0)
    {
        $results = parent::findAll($limit, $offset);

        if ($results && $this->encryptionConfig->autoEncrypt) {
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveData($result);
            }
        }

        return $results;
    }

    /**
     * Override first to decrypt data after retrieval
     */
    public function first()
    {
        $result = parent::first();

        if ($result && $this->encryptionConfig->autoEncrypt) {
            $result = $this->decryptSensitiveData($result);
        }

        return $result;
    }

    /**
     * Get masked data for API responses (sensitive fields masked)
     *
     * @param mixed $data Data to mask
     * @return mixed
     */
    public function getMasked($data)
    {
        if (!$this->encryptionConfig->autoMask) {
            return $data;
        }

        return $this->maskSensitiveData($data);
    }

    /**
     * Decrypt all results from a query builder result
     *
     * @param mixed $results Query builder results
     * @return mixed
     */
    protected function decryptResults($results)
    {
        if (!$results) {
            return $results;
        }

        if (is_array($results)) {
            foreach ($results as &$result) {
                $result = $this->decryptSensitiveData($result);
            }
        } else {
            $results = $this->decryptSensitiveData($results);
        }

        return $results;
    }
}
