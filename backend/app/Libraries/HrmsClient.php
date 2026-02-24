<?php

namespace App\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

class HrmsClient
{
    private $httpClient;
    private $hrmsBaseUrl;
    private $hrmsApiKey;
    private $hrmsApiSecret;
    private $jwtHandler;

    public function __construct()
    {
        $this->hrmsBaseUrl = getenv('hrms.base_url') ?: 'https://hrms.api.example.com';
        $this->hrmsApiKey = getenv('hrms.api_key');
        $this->hrmsApiSecret = getenv('hrms.api_secret');
        
        $this->httpClient = new Client([
            'base_uri' => $this->hrmsBaseUrl,
            'timeout' => 30,
            'verify' => true,
        ]);

        $this->jwtHandler = new JwtHandler();
    }

    /**
     * Validate JWT token received from HRMS
     * @param string $token JWT token from HRMS
     * @return array|null Claims if valid, null if invalid
     */
    public function validateHrmsToken($token)
    {
        try {
            $claims = $this->jwtHandler->validateToken($token);
            log_message('info', 'HRMS Token validated successfully', [
                'hrms_employee_id' => $claims['sub'] ?? null
            ]);
            return $claims;
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
}
