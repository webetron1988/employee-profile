<?php

namespace App\Controllers;

use App\Libraries\JwtHandler;
use App\Libraries\HrmsClient;
use App\Libraries\PermissionChecker;
use App\Models\UserModel;
use App\Models\AuditLogModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class Auth extends BaseController
{
    use ResponseTrait;

    private $jwtHandler;
    private $hrmsClient;
    private $userModel;
    private $auditLogModel;

    public function __construct()
    {
        $this->jwtHandler = new JwtHandler();
        $this->hrmsClient = new HrmsClient();
        $this->userModel = new UserModel();
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * SSO Login - Receive JWT from HRMS and create session
     * POST /auth/sso-login
     */
    public function ssoLogin()
    {
        try {
            // Get JWT token from request (from HRMS)
            $token = $this->request->getHeaderLine('Authorization');
            $token = str_replace('Bearer ', '', $token);

            if (empty($token)) {
                return $this->fail('No authorization token provided', 401);
            }

            // Validate token from HRMS
            $claims = $this->hrmsClient->validateHrmsToken($token);

            if (!$claims) {
                $this->logAuditEvent('sso_login', 'SSO_LOGIN', 'failed', 'Invalid HRMS token', null);
                return $this->fail('Invalid token from HRMS', 401);
            }

            $hrmsEmployeeId = $claims['sub'] ?? null;
            $hrmsEmail = $claims['email'] ?? null;

            if (!$hrmsEmployeeId || !$hrmsEmail) {
                $this->logAuditEvent('sso_login', 'SSO_LOGIN', 'failed', 'Missing required claims', null);
                return $this->fail('Missing required token claims', 400);
            }

            // Check if HRMS is healthy
            if (!$this->hrmsClient->isHealthy()) {
                log_message('warning', 'HRMS is not healthy during SSO login');
                // Continue anyway - user might be already in system
            }

            // Sync or fetch employee data from HRMS
            $employeeData = $this->hrmsClient->syncEmployeeData($hrmsEmployeeId);

            // Check if user exists in system
            $user = $this->userModel->where('email', $hrmsEmail)->first();

            if (!$user) {
                // Create new user if doesn't exist
                $user = $this->createUserFromHrms($hrmsEmployeeId, $hrmsEmail, $employeeData, $claims);
                if (!$user) {
                    return $this->fail('Failed to create user account', 500);
                }
            } else {
                // Update user with fresh HRMS data
                $this->updateUserFromHrms($user['id'], $hrmsEmployeeId, $employeeData, $claims);
            }

            // Fetch permissions from HRMS
            $permissions = $this->hrmsClient->fetchUserPermissions($hrmsEmployeeId);

            // Generate new JWT for application
            $appToken = $this->jwtHandler->generateToken([
                'user_id' => $user['id'],
                'hrms_employee_id' => $hrmsEmployeeId,
                'email' => $hrmsEmail,
                'role' => $user['role'] ?? 'employee',
                'permissions' => $permissions,
                'iat' => time(),
                'sub' => $user['id']
            ], 300); // 5 minutes

            // Generate refresh token
            $refreshToken = $this->jwtHandler->generateRefreshToken([
                'user_id' => $user['id'],
                'type' => 'refresh'
            ]);

            // Update last login
            $this->userModel->update($user['id'], [
                'last_login_at' => date('Y-m-d H:i:s')
            ]);

            // Log successful login
            $this->logAuditEvent('sso_login', 'SSO_LOGIN', 'success', $hrmsEmail, $user['id']);

            return $this->respond([
                'status' => 'success',
                'message' => 'SSO login successful',
                'data' => [
                    'access_token' => $appToken['token'],
                    'expires_in' => $appToken['expiresIn'],
                    'refresh_token' => $refreshToken['token'],
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'hrms_employee_id' => $hrmsEmployeeId
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'SSO login error: ' . $e->getMessage());
            $this->logAuditEvent('sso_login', 'SSO_LOGIN', 'error', $e->getMessage(), null);
            return $this->fail('Authentication failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Refresh Access Token
     * POST /auth/refresh
     */
    public function refresh()
    {
        try {
            $refreshToken = $this->request->getJSON()->refresh_token ?? null;

            if (!$refreshToken) {
                return $this->fail('Refresh token required', 400);
            }

            // Validate refresh token
            $claims = $this->jwtHandler->validateToken($refreshToken);

            if (!$claims || ($claims['type'] ?? null) !== 'refresh') {
                $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'failed', 'Invalid refresh token', $claims['user_id'] ?? null);
                return $this->fail('Invalid refresh token', 401);
            }

            $userId = $claims['user_id'];

            // Fetch user
            $user = $this->userModel->find($userId);

            if (!$user || !$user['is_active']) {
                $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'failed', 'User not found or inactive', $userId);
                return $this->fail('User not found or inactive', 401);
            }

            // Generate new access token
            $permissionChecker = new PermissionChecker($userId);
            $permissions = [
                'modules' => $permissionChecker->getAvailableModules(),
                'data_scope' => $permissionChecker->getDataScope($userId)
            ];

            $newAccessToken = $this->jwtHandler->generateToken([
                'user_id' => $userId,
                'email' => $user['email'],
                'role' => $user['role'],
                'permissions' => $permissions,
                'iat' => time(),
                'sub' => $userId
            ], 300);

            // Log token refresh
            $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'success', 'Token refreshed', $userId);

            return $this->respond([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $newAccessToken['token'],
                    'expires_in' => $newAccessToken['expiresIn']
                ]
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'Token refresh error: ' . $e->getMessage());
            return $this->fail('Token refresh failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify Current Token
     * GET /auth/verify
     */
    public function verify()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            $token = str_replace('Bearer ', '', $token);

            if (empty($token)) {
                return $this->fail('No token provided', 401);
            }

            // Validate token
            $claims = $this->jwtHandler->validateToken($token);

            if (!$claims) {
                return $this->fail('Invalid token', 401);
            }

            $userId = $claims['user_id'] ?? null;

            // Optionally, verify user still exists and is active
            if ($userId) {
                $user = $this->userModel->find($userId);
                if (!$user || !$user['is_active']) {
                    return $this->fail('User not found or inactive', 401);
                }
            }

            return $this->respond([
                'status' => 'success',
                'message' => 'Token is valid',
                'data' => [
                    'claims' => $claims,
                    'expires_at' => date('Y-m-d H:i:s', $claims['exp'] ?? time()),
                    'expires_in' => max(0, ($claims['exp'] ?? 0) - time())
                ]
            ], 200);
        } catch (Exception $e) {
            log_message('warning', 'Token verification error: ' . $e->getMessage());
            return $this->fail('Token verification failed', 401);
        }
    }

    /**
     * Logout - Invalidate session
     * POST /auth/logout
     */
    public function logout()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            $token = str_replace('Bearer ', '', $token);

            // Extract claims for audit logging
            try {
                $claims = $this->jwtHandler->extractClaims($token);
                $userId = $claims['user_id'] ?? null;
            } catch (Exception $e) {
                $userId = null;
            }

            // Log audit event
            $this->logAuditEvent('logout', 'LOGOUT', 'success', 'User logged out', $userId);

            return $this->respond([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ], 200);
        } catch (Exception $e) {
            log_message('error', 'Logout error: ' . $e->getMessage());
            return $this->fail('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create user from HRMS data
     */
    private function createUserFromHrms($hrmsEmployeeId, $email, $employeeData, $claims)
    {
        try {
            // Determine role from HRMS claims or default to employee
            $role = $claims['role'] ?? 'employee';
            $role = in_array($role, ['admin', 'hr', 'manager', 'employee', 'system']) ? $role : 'employee';

            // Fetch permissions from HRMS
            $permissions = $this->hrmsClient->fetchUserPermissions($hrmsEmployeeId);

            $userId = $this->userModel->insert([
                'email' => $email,
                'password_hash' => null, // Not used for SSO
                'employee_id' => $employeeData['employee_id'] ?? null,
                'role' => $role,
                'permissions' => json_encode($permissions),
                'is_active' => 1,
                'last_login_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            log_message('info', 'New user created from HRMS', [
                'user_id' => $userId,
                'email' => $email,
                'hrms_employee_id' => $hrmsEmployeeId
            ]);

            return $this->userModel->find($userId);
        } catch (Exception $e) {
            log_message('error', 'Failed to create user from HRMS: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user from HRMS data
     */
    private function updateUserFromHrms($userId, $hrmsEmployeeId, $employeeData, $claims)
    {
        try {
            // Update role if provided in claims
            $updateData = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (isset($claims['role'])) {
                $role = $claims['role'];
                if (in_array($role, ['admin', 'hr', 'manager', 'employee', 'system'])) {
                    $updateData['role'] = $role;
                }
            }

            // Fetch fresh permissions from HRMS
            $permissions = $this->hrmsClient->fetchUserPermissions($hrmsEmployeeId);
            $updateData['permissions'] = json_encode($permissions);

            $this->userModel->update($userId, $updateData);

            log_message('info', 'User updated from HRMS', [
                'user_id' => $userId,
                'hrms_employee_id' => $hrmsEmployeeId
            ]);

            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to update user from HRMS: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log audit event
     */
    private function logAuditEvent($action, $entityType, $status, $reason, $userId)
    {
        try {
            $this->auditLogModel->insert([
                'user_id' => $userId,
                'employee_id' => null,
                'module' => 'auth',
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => null,
                'old_value' => null,
                'new_value' => $status,
                'change_reason' => $reason,
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            log_message('error', 'Failed to log audit event: ' . $e->getMessage());
        }
    }

    /**
     * Get client IP address
     */
    private function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
    }
}
