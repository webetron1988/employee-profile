<?php

namespace App\Middleware;

use App\Libraries\JwtHandler;
use App\Libraries\PermissionChecker;
use App\Models\AuditLog as AuditLogModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class PermissionMiddleware implements FilterInterface
{
    private $jwtHandler;
    private $auditLogModel;

    // Routes that don't require authentication
    private const PUBLIC_ROUTES = [
        'auth/sso-login',
        'health',
        'docs'
    ];

    // Routes that require specific permissions
    private const PROTECTED_ROUTES = [
        'profile/read' => ['personal-profile', 'read'],
        'profile/write' => ['personal-profile', 'write'],
        'profile/delete' => ['personal-profile', 'delete-own'],
        'job/read' => ['job-organization', 'read'],
        'job/write' => ['job-organization', 'write'],
        'performance/read' => ['performance', 'read'],
        'performance/write' => ['performance', 'write'],
        'performance/approve' => ['performance', 'approve'],
        'talent/read' => ['talent-management', 'read'],
        'talent/write' => ['talent-management', 'write'],
        'learning/read' => ['learning-development', 'read'],
        'learning/enroll' => ['learning-development', 'enroll']
    ];

    public function __construct()
    {
        $this->jwtHandler = new JwtHandler();
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * Process the request and enforce permissions
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getPath();

        // Check if route is public
        if ($this->isPublicRoute($uri)) {
            return null;
        }

        // Get authorization token
        $token = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return $this->buildUnauthorizedResponse('No token provided');
        }

        try {
            // Validate JWT token — returns ['status', 'claims', 'data']
            $result = $this->jwtHandler->validateToken($token);

            if (!$result || !($result['status'] ?? false)) {
                return $this->buildUnauthorizedResponse('Invalid token');
            }

            // Our payload data lives inside the 'data' key
            $data = $result['data'] ?? [];

            if (empty($data['user_id'])) {
                return $this->buildUnauthorizedResponse('Invalid token claims');
            }

            // Attach user data to request via setHeader (avoids deprecated dynamic properties)
            $request->setHeader('X-Auth-User-Id',       (string) $data['user_id']);
            $request->setHeader('X-Auth-Hrms-Emp-Id',   (string) ($data['hrms_employee_id'] ?? ''));
            $request->setHeader('X-Auth-Role',          $data['role'] ?? 'employee');
            $request->setHeader('X-Auth-Email',         $data['email'] ?? '');
            $request->setHeader('X-Auth-Permissions',   json_encode($data['permissions'] ?? []));
            $request->setHeader('X-Auth-Employee-Id',   (string) $data['user_id']);

            // Check module and action permissions (pass parsed data, not dynamic props)
            if (!$this->checkPermissions($request, $data)) {
                return $this->buildForbiddenResponse('Insufficient permissions');
            }

            // Log access attempt
            $this->logAccessAttempt($request, 'allowed', $data);

            return null;
        } catch (Exception $e) {
            log_message('warning', 'Permission middleware error: ' . $e->getMessage());
            return $this->buildUnauthorizedResponse('Token validation failed');
        }
    }

    /**
     * Process response after controller
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Apply field masking to response if needed
        $authUserId = $request->getHeaderLine('X-Auth-User-Id');
        if ($authUserId && $response) {
            $permissionChecker = new PermissionChecker((int) $authUserId);

            // Get response body
            $body = $response->getBody();
            $contentType = $response->getHeader('Content-Type');

            // Only process JSON responses
            if (strpos($contentType, 'application/json') !== false && !empty($body)) {
                try {
                    $data = json_decode($body, true);

                    // Mask sensitive fields based on permissions
                    $data = $this->maskResponseData($data, $permissionChecker, $request);

                    // Set masked response
                    $response->setBody(json_encode($data));
                } catch (Exception $e) {
                    log_message('error', 'Failed to mask response data: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }

    /**
     * Check if token has required permissions for route
     */
    private function checkPermissions(RequestInterface $request, $claims)
    {
        $uri = $request->getPath();
        $method = $request->getMethod();

        // Check against protected routes
        foreach (self::PROTECTED_ROUTES as $route => $requiredPerms) {
            if (strpos($uri, $route) === 0) {
                [$module, $action] = $requiredPerms;

                $permissionChecker = new PermissionChecker($claims['user_id'] ?? null);

                // Check module access
                if (!$permissionChecker->hasModuleAccess($module)) {
                    log_message('warning', 'Module access denied', [
                        'user_id' => $claims['user_id'] ?? null,
                        'module' => $module,
                        'uri' => $uri
                    ]);
                    return false;
                }

                // Check action access
                if (!$permissionChecker->hasActionAccess($module, $action)) {
                    log_message('warning', 'Action access denied', [
                        'user_id' => $claims['user_id'] ?? null,
                        'module' => $module,
                        'action' => $action,
                        'uri' => $uri
                    ]);
                    return false;
                }

                return true;
            }
        }

        // If not in protected routes, allow (controller will do final check)
        return true;
    }

    /**
     * Mask sensitive fields in response data
     */
    private function maskResponseData($data, PermissionChecker $permissionChecker, RequestInterface $request)
    {
        if (!is_array($data)) {
            return $data;
        }

        // Check if this is wrapped response (has 'data' key)
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = $this->applyMasking($data['data'], $permissionChecker, $request);
        } else {
            $data = $this->applyMasking($data, $permissionChecker, $request);
        }

        return $data;
    }

    /**
     * Apply field masking based on role and permissions
     */
    private function applyMasking($data, PermissionChecker $permissionChecker, RequestInterface $request)
    {
        if (!is_array($data)) {
            return $data;
        }

        // Check if data is array of records or single record
        if ($this->isSingleRecord($data)) {
            // Single record - mask if not own data or not authorized
            if (!$this->isOwnData($data, $request)) {
                $data = $permissionChecker->maskSensitiveFields($data);
            }
        } else if ($this->isRecordArray($data)) {
            // Array of records - mask each
            foreach ($data as &$record) {
                if (is_array($record) && !$this->isOwnData($record, $request)) {
                    $record = $permissionChecker->maskSensitiveFields($record);
                }
            }
        }

        return $data;
    }

    /**
     * Check if record belongs to current user
     */
    private function isOwnData($record, RequestInterface $request)
    {
        $authUserId = $request->getHeaderLine('X-Auth-User-Id');
        if (!$authUserId) {
            return false;
        }

        // Check various identifier fields
        $identifierFields = ['employee_id', 'user_id', 'id'];

        foreach ($identifierFields as $field) {
            if (isset($record[$field]) && $record[$field] == $authUserId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if array represents a single record
     */
    private function isSingleRecord($data)
    {
        if (empty($data)) {
            return false;
        }

        // Check if has typical record fields
        $recordFields = ['id', 'employee_id', 'user_id', 'email', 'created_at'];

        foreach ($recordFields as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if array is list of records
     */
    private function isRecordArray($data)
    {
        if (!is_array($data) || empty($data)) {
            return false;
        }

        // Get first element
        $first = reset($data);

        if (!is_array($first)) {
            return false;
        }

        // Check if first element has record-like structure
        return $this->isSingleRecord($first);
    }

    /**
     * Log access attempt
     */
    private function logAccessAttempt(RequestInterface $request, $status, $claims)
    {
        try {
            $this->auditLogModel->insert([
                'user_id' => $claims['user_id'] ?? $request->getHeaderLine('X-Auth-User-Id') ?: null,
                'employee_id' => null,
                'module' => 'api',
                'action' => strtoupper($request->getMethod()),
                'entity_type' => 'api_request',
                'entity_id' => $request->getPath(),
                'old_value' => null,
                'new_value' => $status,
                'change_reason' => $request->getPath(),
                'ip_address' => $this->getIpAddress($request),
                'user_agent' => $request->getHeaderLine('User-Agent'),
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            log_message('error', 'Failed to log access attempt: ' . $e->getMessage());
        }
    }

    /**
     * Build a 401 Unauthorized response
     */
    private function buildUnauthorizedResponse(string $message): ResponseInterface
    {
        return response()
            ->setStatusCode(401)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'status' => 'error',
                'message' => $message,
                'code' => 'UNAUTHORIZED'
            ]));
    }

    /**
     * Build a 403 Forbidden response
     */
    private function buildForbiddenResponse(string $message): ResponseInterface
    {
        return response()
            ->setStatusCode(403)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'status' => 'error',
                'message' => $message,
                'code' => 'FORBIDDEN'
            ]));
    }

    /**
     * Check if route is public (doesn't require auth)
     */
    private function isPublicRoute($uri)
    {
        foreach (self::PUBLIC_ROUTES as $route) {
            if (strpos($uri, $route) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get client IP address
     */
    private function getIpAddress(RequestInterface $request)
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        return 'Unknown';
    }
}
