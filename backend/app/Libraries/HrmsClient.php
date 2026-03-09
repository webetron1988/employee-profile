<?php

namespace App\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class HrmsClient
{
    private $httpClient;
    private $hrmsBaseUrl;
    private $hrmsApiKey;
    private $hrmsApiSecret;
    private $hrmsJwtKey;
    private $jwtHandler;

    public function __construct()
    {
        $this->hrmsBaseUrl = getenv('hrms.base_url') ?: 'https://hrms.api.example.com';
        $this->hrmsApiKey = getenv('hrms.api_key');
        $this->hrmsApiSecret = getenv('hrms.api_secret');
        // Force-read from .env (CI4 DotEnv won't overwrite cached vars)
        $this->hrmsJwtKey = $this->readEnvKey('hrms.jwt_secret_key') ?: '';

        $this->httpClient = new Client([
            'base_uri' => $this->hrmsBaseUrl,
            'timeout' => 30,
            'verify' => true,
        ]);

        $this->jwtHandler = new JwtHandler();
    }

    /**
     * Validate JWT token received from HRMS.
     *
     * Tries HS256 first (real HRMS tokens signed with shared key), then
     * falls back to RS256 (dev tokens signed with this app's own key pair).
     *
     * @param string $token JWT token (HRMS-issued HS256, or locally-signed RS256 for dev)
     * @return array|null Flat claims array ['sub', 'email', 'role'] if valid, null if invalid
     */
    public function validateHrmsToken($token)
    {
        // ── Try 1: HS256 validation using HRMS shared key ──
        if (!empty($this->hrmsJwtKey)) {
            try {
                $decoded = JWT::decode($token, new Key($this->hrmsJwtKey, 'HS256'));
                $data = (array) $decoded;

                // Verify this is an SSO token from HRMS
                if (($data['type'] ?? '') === 'ep_sso') {
                    // HRMS tokens use API_TIME instead of exp — enforce 120-second window
                    if (!empty($data['API_TIME']) && (time() - (int) $data['API_TIME']) > 120) {
                        log_message('warning', 'HRMS SSO token expired (age > 120s)');
                        return null;
                    }

                    log_message('info', 'HRMS HS256 SSO token validated', ['sub' => $data['sub'] ?? null]);

                    return [
                        'sub'   => $data['sub'] ?? (string) ($data['empID'] ?? ''),
                        'email' => $data['email'] ?? null,
                        'role'  => $data['role'] ?? 'employee',
                    ];
                }

                // Generic HRMS HS256 token (non-SSO)
                log_message('info', 'HRMS HS256 token validated', ['email' => $data['email'] ?? null]);
                return [
                    'sub'   => $data['sub'] ?? $data['empID'] ?? (string) ($data['id'] ?? ''),
                    'email' => $data['email'] ?? null,
                    'role'  => $data['role'] ?? $data['role_code'] ?? 'employee',
                ];
            } catch (Exception $e) {
                log_message('info', 'HS256 validation failed, trying RS256: ' . $e->getMessage());
            }
        }

        // ── Try 2: RS256 validation (dev mode — token signed with this app's RSA key) ──
        try {
            $result = $this->jwtHandler->validateToken($token);

            if (!$result || !($result['status'] ?? false)) {
                log_message('warning', 'HRMS Token validation failed: invalid or expired');
                return null;
            }

            $data = $result['data'] ?? [];

            return [
                'sub'   => $data['hrms_employee_id'] ?? (string) ($data['user_id'] ?? ''),
                'email' => $data['email'] ?? null,
                'role'  => $data['role'] ?? 'employee',
            ];
        } catch (Exception $e) {
            log_message('warning', 'HRMS Token validation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch user permissions from HRMS
     * @param string $hrmsEmployeeId Employee ID in HRMS
     * @return array Permissions array
     */
    public function fetchUserPermissions($hrmsEmployeeId)
    {
        try {
            $response = $this->makeRequest('GET', "/api/employees/{$hrmsEmployeeId}/permissions");

            if (!$response || !isset($response['data'])) {
                log_message('warning', 'No permissions data from HRMS', [
                    'hrms_employee_id' => $hrmsEmployeeId
                ]);
                return $this->getDefaultPermissions();
            }

            log_message('info', 'User permissions fetched from HRMS', [
                'hrms_employee_id' => $hrmsEmployeeId,
                'permission_count' => count($response['data'])
            ]);

            return $this->formatPermissions($response['data']);
        } catch (Exception $e) {
            log_message('error', 'Failed to fetch permissions from HRMS: ' . $e->getMessage(), [
                'hrms_employee_id' => $hrmsEmployeeId
            ]);
            return $this->getDefaultPermissions();
        }
    }

    /**
     * Sync employee data from HRMS
     * @param string $hrmsEmployeeId Employee ID in HRMS
     * @return array|null Synced employee data
     */
    public function syncEmployeeData($hrmsEmployeeId)
    {
        try {
            $response = $this->makeRequest('GET', "/api/employees/{$hrmsEmployeeId}");

            if (!$response || !isset($response['data'])) {
                log_message('warning', 'No employee data from HRMS', [
                    'hrms_employee_id' => $hrmsEmployeeId
                ]);
                return null;
            }

            $employeeData = $this->formatEmployeeData($response['data']);

            log_message('info', 'Employee data synced from HRMS', [
                'hrms_employee_id' => $hrmsEmployeeId,
                'name' => $employeeData['full_name'] ?? 'Unknown'
            ]);

            return $employeeData;
        } catch (Exception $e) {
            log_message('error', 'Failed to sync employee data from HRMS: ' . $e->getMessage(), [
                'hrms_employee_id' => $hrmsEmployeeId
            ]);
            return null;
        }
    }

    /**
     * Fetch organization hierarchy from HRMS
     * @param string $hrmsEmployeeId Employee ID in HRMS
     * @return array|null Organization hierarchy
     */
    public function fetchOrgHierarchy($hrmsEmployeeId)
    {
        try {
            $response = $this->makeRequest('GET', "/api/employees/{$hrmsEmployeeId}/org-hierarchy");

            if (!$response || !isset($response['data'])) {
                log_message('warning', 'No org hierarchy data from HRMS', [
                    'hrms_employee_id' => $hrmsEmployeeId
                ]);
                return null;
            }

            log_message('info', 'Org hierarchy fetched from HRMS', [
                'hrms_employee_id' => $hrmsEmployeeId
            ]);

            return $response['data'];
        } catch (Exception $e) {
            log_message('error', 'Failed to fetch org hierarchy from HRMS: ' . $e->getMessage(), [
                'hrms_employee_id' => $hrmsEmployeeId
            ]);
            return null;
        }
    }

    /**
     * Batch sync multiple employees
     * @param array $hrmsEmployeeIds Array of HRMS employee IDs
     * @return array Sync results with success/failure counts
     */
    public function batchSyncEmployees($hrmsEmployeeIds)
    {
        $results = [
            'total' => count($hrmsEmployeeIds),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($hrmsEmployeeIds as $hrmsEmployeeId) {
            try {
                $data = $this->syncEmployeeData($hrmsEmployeeId);
                if ($data) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "No data received for {$hrmsEmployeeId}";
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$hrmsEmployeeId}: " . $e->getMessage();
            }
        }

        log_message('info', 'Batch sync completed', $results);
        return $results;
    }

    /**
     * Get SSO endpoint from HRMS
     * @return string|null SSO endpoint URL
     */
    public function getSsoEndpoint()
    {
        try {
            $response = $this->makeRequest('GET', '/api/auth/sso-endpoint');

            if ($response && isset($response['endpoint'])) {
                return $response['endpoint'];
            }

            return $this->hrmsBaseUrl . '/api/auth/sso';
        } catch (Exception $e) {
            log_message('error', 'Failed to fetch SSO endpoint: ' . $e->getMessage());
            return $this->hrmsBaseUrl . '/api/auth/sso';
        }
    }

    /**
     * Check if HRMS is available
     * @return bool
     */
    public function isHealthy()
    {
        try {
            $response = $this->makeRequest('GET', '/api/health');
            return isset($response['status']) && $response['status'] === 'ok';
        } catch (Exception $e) {
            log_message('warning', 'HRMS health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Make HTTP request to HRMS API
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array|null Response data
     */
    private function makeRequest($method, $endpoint, $options = [])
    {
        try {
            $defaultOptions = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->hrmsApiKey,
                    'X-API-Secret' => $this->hrmsApiSecret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ];

            $mergedOptions = array_merge_recursive($defaultOptions, $options);

            $response = $this->httpClient->request($method, $endpoint, $mergedOptions);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new Exception("HTTP {$statusCode} from HRMS");
            }

            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (GuzzleException $e) {
            log_message('error', 'HRMS API request failed: ' . $e->getMessage(), [
                'method' => $method,
                'endpoint' => $endpoint
            ]);
            throw new Exception('HRMS API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Format employee data response from HRMS
     * @param array $hrmsData Raw data from HRMS
     * @return array Formatted data
     */
    private function formatEmployeeData($hrmsData)
    {
        return [
            'hrms_employee_id' => $hrmsData['employee_id'] ?? null,
            'email' => $hrmsData['email'] ?? null,
            'first_name' => $hrmsData['first_name'] ?? null,
            'last_name' => $hrmsData['last_name'] ?? null,
            'full_name' => trim(($hrmsData['first_name'] ?? '') . ' ' . ($hrmsData['last_name'] ?? '')),
            'date_of_birth' => $hrmsData['date_of_birth'] ?? null,
            'phone' => $hrmsData['phone'] ?? null,
            'department' => $hrmsData['department'] ?? null,
            'designation' => $hrmsData['designation'] ?? null,
            'manager_id' => $hrmsData['manager_id'] ?? null,
            'status' => $hrmsData['status'] ?? 'active',
            'last_synced_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Format permissions response from HRMS
     * @param array $hrmsPermissions Raw permissions from HRMS
     * @return array Formatted permissions
     */
    private function formatPermissions($hrmsPermissions)
    {
        $formatted = [
            'modules' => [],
            'actions' => [],
            'data_scope' => []
        ];

        foreach ($hrmsPermissions as $permission) {
            if (isset($permission['module'])) {
                $formatted['modules'][] = $permission['module'];
            }
            if (isset($permission['action'])) {
                $formatted['actions'][] = $permission['action'];
            }
            if (isset($permission['data_scope'])) {
                $formatted['data_scope'][] = $permission['data_scope'];
            }
        }

        return $formatted;
    }

    /**
     * Get default permissions for new users
     * @return array Default permissions
     */
    private function getDefaultPermissions()
    {
        return [
            'modules' => ['personal-profile', 'job-organization'],
            'actions' => ['read'],
            'data_scope' => ['self']
        ];
    }

    /**
     * Read a key directly from the .env file, bypassing getenv() cache.
     * CI4's DotEnv won't overwrite existing env vars, so stale values
     * persist across server restarts.
     */
    private function readEnvKey(string $key): string
    {
        $envFile = ROOTPATH . '.env';
        if (!is_file($envFile)) {
            return getenv($key) ?: '';
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === $key) {
                // Strip surrounding quotes
                if ((strlen($value) > 1) && ($value[0] === "'" || $value[0] === '"')) {
                    $value = substr($value, 1, -1);
                }
                return $value;
            }
        }

        return getenv($key) ?: '';
    }
}
