<?php

namespace App\Controllers;

use App\Libraries\JwtHandler;
use App\Libraries\HrmsClient;
use App\Libraries\PermissionChecker;
use App\Models\User as UserModel;
use App\Models\HrmsEmployee as HrmsEmployeeModel;
use App\Models\AuditLog as AuditLogModel;
use CodeIgniter\API\ResponseTrait;
use Exception;

class Auth extends BaseController
{
    use ResponseTrait;

    private $jwtHandler;
    private $hrmsClient;
    private $userModel;
    private $hrmsEmployeeModel;
    private $auditLogModel;

    public function __construct()
    {
        $this->jwtHandler = new JwtHandler();
        $this->hrmsClient = new HrmsClient();
        $this->userModel = new UserModel();
        $this->hrmsEmployeeModel = new HrmsEmployeeModel();
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * SSO Login - Receive JWT from HRMS and create session
     * POST /auth/sso-login
     */
    public function ssoLogin()
    {
        try {
            // Get JWT token from request body first, fallback to Authorization header
            $body = $this->request->getJSON(true);
            $token = $body['token'] ?? null;

            if (empty($token)) {
                $token = $this->request->getHeaderLine('Authorization');
                $token = str_replace('Bearer ', '', $token);
            }

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

            // Sync employee + user from HRMS database directly
            $syncResult = $this->syncUserFromHrmsDb($hrmsEmployeeId, $hrmsEmail, $claims);

            if (!$syncResult) {
                return $this->fail('Failed to sync user from HRMS', 500);
            }

            // Fetch the user record (freshly synced)
            $user = $this->userModel->where('email', $hrmsEmail)->first();

            if (!$user) {
                return $this->fail('User not found after sync', 500);
            }

            // Build permissions from role
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
            ], 3600); // 1 hour

            // Generate refresh token
            $refreshToken = $this->jwtHandler->generateRefreshToken([
                'user_id' => $user['id'],
                'type' => 'refresh'
            ]);

            // Store refresh token hash for rotation (single-use enforcement)
            $this->userModel->update($user['id'], [
                'last_login_at' => date('Y-m-d H:i:s'),
                'refresh_token_hash' => hash('sha256', $refreshToken['token']),
            ]);

            // Log successful login
            $this->logAuditEvent('sso_login', 'SSO_LOGIN', 'success', $hrmsEmail, $user['id']);

            return $this->respond([
                'status' => 'success',
                'message' => 'SSO login successful',
                'data' => [
                    'access_token' => $appToken['token'],
                    'expires_in' => $appToken['expires_in'],
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
     * Email + Password Login
     * POST /auth/login
     */
    public function login()
    {
        try {
            $body = $this->request->getJSON(true);
            $email    = trim($body['email'] ?? '');
            $password = $body['password'] ?? '';

            if (empty($email) || empty($password)) {
                return $this->fail(['email' => 'Email and password are required'], 400);
            }

            // Look up user by email
            $user = $this->userModel->where('email', $email)->first();

            if (!$user) {
                $this->logAuditEvent('login', 'PASSWORD_LOGIN', 'failed', 'User not found: ' . $email, null);
                return $this->fail('Invalid email or password', 401);
            }

            // Check account is active
            if (!$user['is_active']) {
                $this->logAuditEvent('login', 'PASSWORD_LOGIN', 'failed', 'Inactive account: ' . $email, $user['id']);
                return $this->fail('Your account is inactive. Please contact HR.', 403);
            }

            // Verify password
            if (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
                $this->logAuditEvent('login', 'PASSWORD_LOGIN', 'failed', 'Wrong password: ' . $email, $user['id']);
                return $this->fail('Invalid email or password', 401);
            }

            // Fetch permissions
            $permissions = $this->hrmsClient->fetchUserPermissions($user['hrms_employee_id'] ?? null);

            // Generate application token
            $appToken = $this->jwtHandler->generateToken([
                'user_id'          => $user['id'],
                'hrms_employee_id' => $user['hrms_employee_id'] ?? null,
                'email'            => $user['email'],
                'role'             => $user['role'] ?? 'employee',
                'permissions'      => $permissions,
                'iat'              => time(),
                'sub'              => $user['id'],
            ], 3600); // 1 hour

            // Generate refresh token
            $refreshToken = $this->jwtHandler->generateRefreshToken([
                'user_id' => $user['id'],
                'type'    => 'refresh',
            ]);

            // Store refresh token hash for rotation (single-use enforcement)
            $this->userModel->update($user['id'], [
                'last_login_at' => date('Y-m-d H:i:s'),
                'refresh_token_hash' => hash('sha256', $refreshToken['token']),
            ]);

            $this->logAuditEvent('login', 'PASSWORD_LOGIN', 'success', $email, $user['id']);

            return $this->respond([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => [
                    'access_token'  => $appToken['token'],
                    'expires_in'    => $appToken['expires_in'],
                    'refresh_token' => $refreshToken['token'],
                    'user' => [
                        'id'                => $user['id'],
                        'email'             => $user['email'],
                        'role'              => $user['role'],
                        'hrms_employee_id'  => $user['hrms_employee_id'] ?? null,
                        'first_name'        => $user['first_name'] ?? '',
                        'last_name'         => $user['last_name'] ?? '',
                    ],
                ],
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Password login error: ' . $e->getMessage());
            return $this->fail('Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Service Login — server-to-server token issuance using API key + user ID.
     * Used by token_refresh.php when refresh token has expired.
     * POST /auth/service-login
     */
    public function serviceLogin()
    {
        try {
            $apiKey = $this->request->getHeaderLine('X-Api-Key');
            $expectedKey = env('EP_API_KEY');

            if (empty($expectedKey) || empty($apiKey) || !hash_equals($expectedKey, $apiKey)) {
                return $this->fail('Unauthorized', 401);
            }

            $body = $this->request->getJSON(true);
            $userId = $body['user_id'] ?? null;
            $hrmsEmpId = $body['hrms_employee_id'] ?? null;

            if (empty($userId) && empty($hrmsEmpId)) {
                return $this->fail('user_id or hrms_employee_id required', 400);
            }

            // Find user
            if ($userId) {
                $user = $this->userModel->find($userId);
            } else {
                $user = $this->userModel->where('hrms_employee_id', $hrmsEmpId)->first();
            }

            if (!$user || !$user['is_active']) {
                return $this->fail('User not found or inactive', 404);
            }

            $permissions = $this->hrmsClient->fetchUserPermissions($user['hrms_employee_id'] ?? null);

            $appToken = $this->jwtHandler->generateToken([
                'user_id'          => $user['id'],
                'hrms_employee_id' => $user['hrms_employee_id'] ?? null,
                'email'            => $user['email'],
                'role'             => $user['role'] ?? 'employee',
                'permissions'      => $permissions,
                'iat'              => time(),
                'sub'              => $user['id'],
            ], 3600);

            $refreshToken = $this->jwtHandler->generateRefreshToken([
                'user_id' => $user['id'],
                'type'    => 'refresh',
            ]);

            $this->userModel->update($user['id'], [
                'last_login_at'      => date('Y-m-d H:i:s'),
                'refresh_token_hash' => hash('sha256', $refreshToken['token']),
            ]);

            $this->logAuditEvent('login', 'SERVICE_LOGIN', 'success', 'Service login for user ' . $user['id'], $user['id']);

            return $this->respond([
                'status'  => 'success',
                'data'    => [
                    'access_token'  => $appToken['token'],
                    'expires_in'    => $appToken['expires_in'],
                    'refresh_token' => $refreshToken['token'],
                    'user' => [
                        'id'                => $user['id'],
                        'email'             => $user['email'],
                        'role'              => $user['role'],
                        'hrms_employee_id'  => $user['hrms_employee_id'] ?? null,
                        'first_name'        => $user['first_name'] ?? '',
                        'last_name'         => $user['last_name'] ?? '',
                    ],
                ],
            ]);
        } catch (Exception $e) {
            log_message('error', 'Service login error: ' . $e->getMessage());
            return $this->fail('Service login failed', 500);
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

            // Validate refresh token JWT
            $claims = $this->jwtHandler->validateToken($refreshToken);

            if (!$claims || !($claims['status'] ?? false)) {
                $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'failed', 'Invalid refresh token', null);
                return $this->fail('Invalid refresh token', 401);
            }

            $tokenData = $claims['data'] ?? [];
            if (($tokenData['type'] ?? null) !== 'refresh') {
                $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'failed', 'Not a refresh token', null);
                return $this->fail('Invalid refresh token', 401);
            }

            $userId = $tokenData['user_id'] ?? null;

            // Fetch user
            $user = $this->userModel->find($userId);

            if (!$user || !$user['is_active']) {
                $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'failed', 'User not found or inactive', $userId);
                return $this->fail('User not found or inactive', 401);
            }

            // Verify refresh token hash matches (single-use enforcement)
            $presentedHash = hash('sha256', $refreshToken);
            if (empty($user['refresh_token_hash']) || !hash_equals($user['refresh_token_hash'], $presentedHash)) {
                // Token reuse detected — revoke all sessions for this user
                $this->userModel->update($userId, ['refresh_token_hash' => null]);
                $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'failed', 'Refresh token reuse detected — revoked', $userId);
                return $this->fail('Refresh token has been revoked. Please login again.', 401);
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

            // Rotate: generate new refresh token and store its hash
            $newRefreshToken = $this->jwtHandler->generateRefreshToken([
                'user_id' => $userId,
                'type'    => 'refresh',
            ]);

            $this->userModel->update($userId, [
                'refresh_token_hash' => hash('sha256', $newRefreshToken['token']),
            ]);

            // Log token refresh
            $this->logAuditEvent('token_refresh', 'TOKEN_REFRESH', 'success', 'Token rotated', $userId);

            return $this->respond([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $newAccessToken['token'],
                    'expires_in' => $newAccessToken['expires_in'],
                    'refresh_token' => $newRefreshToken['token'],
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

            // Revoke refresh token by clearing hash
            if ($userId) {
                $this->userModel->update($userId, ['refresh_token_hash' => null]);
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
     * Sync user from HRMS database directly.
     * Creates or updates the `users` table in EP DB (hrms_employee_id stored on users).
     *
     * @param string $hrmsEmployeeId  HRMS empID
     * @param string $email           Employee email
     * @param array  $claims          JWT claims (fallback for role)
     * @return bool
     */
    private function syncUserFromHrmsDb(string $hrmsEmployeeId, string $email, array $claims): bool
    {
        try {
            // Read directly from HRMS database
            $hrmsRow = $this->hrmsEmployeeModel->getByEmpId((int) $hrmsEmployeeId);

            if (!$hrmsRow) {
                $hrmsRow = $this->hrmsEmployeeModel->getByEmail($email);
            }

            if (!$hrmsRow) {
                log_message('warning', 'Employee not found in HRMS DB', [
                    'hrms_employee_id' => $hrmsEmployeeId,
                    'email' => $email,
                ]);
                return $this->syncFromClaimsOnly($hrmsEmployeeId, $email, $claims);
            }

            // Format HRMS data for users table
            $userData = $this->hrmsEmployeeModel->formatForEp($hrmsRow);

            // Use role from HRMS DB, fall back to JWT claim
            if ($userData['role'] === 'employee' && isset($claims['role'])) {
                $claimRole = $claims['role'];
                if (in_array($claimRole, ['admin', 'hr', 'manager', 'system'])) {
                    $userData['role'] = $claimRole;
                }
            }

            // ── Sync users table directly ──
            $existingUser = $this->userModel->where('email', $email)->first();

            if ($existingUser) {
                $userData['updated_at'] = date('Y-m-d H:i:s');
                $this->userModel->skipValidation(true)->update($existingUser['id'], $userData);
                log_message('info', 'User updated from HRMS DB', ['user_id' => $existingUser['id']]);
            } else {
                $userData['password_hash'] = null;
                $userData['permissions']   = json_encode([]);
                $userData['last_login_at'] = date('Y-m-d H:i:s');
                $userData['created_at']    = date('Y-m-d H:i:s');
                $userData['updated_at']    = date('Y-m-d H:i:s');
                $this->userModel->skipValidation(true)->insert($userData);
                log_message('info', 'User created from HRMS DB', ['email' => $email]);
            }

            return true;
        } catch (Exception $e) {
            log_message('error', 'syncUserFromHrmsDb failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fallback sync when HRMS DB row is unavailable — uses JWT claims only.
     */
    private function syncFromClaimsOnly(string $hrmsEmployeeId, string $email, array $claims): bool
    {
        try {
            $role = $claims['role'] ?? 'employee';
            $role = \in_array($role, ['admin', 'hr', 'manager', 'employee', 'system']) ? $role : 'employee';

            $existingUser = $this->userModel->where('email', $email)->first();

            if (!$existingUser) {
                $this->userModel->skipValidation(true)->insert([
                    'email'             => $email,
                    'password_hash'     => null,
                    'hrms_employee_id'  => $hrmsEmployeeId,
                    'first_name'        => explode('@', $email)[0],
                    'last_name'         => '',
                    'role'              => $role,
                    'permissions'       => json_encode([]),
                    'is_active'         => 1,
                    'last_login_at'     => date('Y-m-d H:i:s'),
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->userModel->skipValidation(true)->update($existingUser['id'], [
                    'hrms_employee_id' => $hrmsEmployeeId,
                    'role'             => $role,
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            return true;
        } catch (Exception $e) {
            log_message('error', 'syncFromClaimsOnly failed: ' . $e->getMessage());
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
