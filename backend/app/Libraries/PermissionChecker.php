<?php

namespace App\Libraries;

use App\Models\UserModel;
use App\Models\AuditLogModel;
use Exception;

class PermissionChecker
{
    private $userId;
    private $role;
    private $permissions;
    private $AuditLogModel;

    // Module access levels
    private const MODULE_PERMISSIONS = [
        'personal-profile' => ['read', 'write', 'delete-own'],
        'job-organization' => ['read', 'write', 'delete'],
        'performance' => ['read', 'write', 'comment', 'rate'],
        'talent-management' => ['read', 'write', 'approve'],
        'learning-development' => ['read', 'write', 'enroll']
    ];

    // Role-based module access
    private const ROLE_MODULE_ACCESS = [
        'admin' => ['personal-profile', 'job-organization', 'performance', 'talent-management', 'learning-development'],
        'hr' => ['personal-profile', 'job-organization', 'performance', 'talent-management', 'learning-development'],
        'manager' => ['personal-profile', 'job-organization', 'performance', 'talent-management', 'learning-development'],
        'employee' => ['personal-profile', 'job-organization', 'performance', 'learning-development'],
        'system' => ['personal-profile', 'job-organization', 'performance', 'talent-management', 'learning-development']
    ];

    // Action permissions by role
    private const ROLE_ACTION_ACCESS = [
        'admin' => [
            'personal-profile' => ['read', 'write', 'delete-own', 'export'],
            'job-organization' => ['read', 'write', 'delete', 'export'],
            'performance' => ['read', 'write', 'delete', 'approve', 'comment', 'rate', 'export'],
            'talent-management' => ['read', 'write', 'delete', 'approve', 'export'],
            'learning-development' => ['read', 'write', 'delete', 'approve', 'enroll', 'export']
        ],
        'hr' => [
            'personal-profile' => ['read', 'write', 'export'],
            'job-organization' => ['read', 'write', 'export'],
            'performance' => ['read', 'write', 'approve', 'export'],
            'talent-management' => ['read', 'write', 'approve', 'export'],
            'learning-development' => ['read', 'write', 'approve', 'export']
        ],
        'manager' => [
            'personal-profile' => ['read'],
            'job-organization' => ['read'],
            'performance' => ['read', 'write', 'approve', 'comment', 'rate'],
            'talent-management' => ['read', 'write'],
            'learning-development' => ['read']
        ],
        'employee' => [
            'personal-profile' => ['read', 'write', 'delete-own'],
            'job-organization' => ['read'],
            'performance' => ['read', 'comment'],
            'learning-development' => ['read', 'enroll']
        ],
        'system' => [
            'personal-profile' => ['read', 'write'],
            'job-organization' => ['read', 'write'],
            'performance' => ['read', 'write'],
            'talent-management' => ['read', 'write'],
            'learning-development' => ['read', 'write']
        ]
    ];

    // Data scope rules
    private const DATA_SCOPE_RULES = [
        'admin' => ['all'],
        'hr' => ['department', 'all'],
        'manager' => ['team', 'self'],
        'employee' => ['self'],
        'system' => ['all']
    ];

    // Sensitive fields that require masking
    private const SENSITIVE_FIELDS = [
        'passport_number' => 'govt_id',
        'passport_number_encrypted' => 'govt_id',
        'work_authorization_number' => 'govt_id',
        'work_authorization_number_encrypted' => 'govt_id',
        'bank_account' => 'bank_account',
        'bank_account_encrypted' => 'bank_account',
        'email' => 'email',
        'phone' => 'phone',
        'salary' => 'salary',
        'aadhaar' => 'govt_id',
        'aadhaar_encrypted' => 'govt_id'
    ];

    public function __construct($userId = null)
    {
        $this->userId = $userId;
        try {
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            if ($user) {
                $this->role = $user['role'] ?? 'employee';
                $this->permissions = json_decode($user['permissions'] ?? '{}', true);
            }
        } catch (Exception $e) {
            log_message('error', 'Failed to load user permissions: ' . $e->getMessage());
            $this->role = 'employee';
            $this->permissions = [];
        }

        $this->AuditLogModel = new AuditLogModel();
    }

    /**
     * Check if user has access to a module
     */
    public function hasModuleAccess($module)
    {
        $roles = $this->ROLE_MODULE_ACCESS[$this->role] ?? [];
        $hasAccess = in_array($module, $roles);

        $this->logAccessAttempt('module_check', $module, $hasAccess ? 'allowed' : 'denied');

        return $hasAccess;
    }

    /**
     * Check if user can perform an action on a module
     */
    public function hasActionAccess($module, $action)
    {
        if (!$this->hasModuleAccess($module)) {
            return false;
        }

        $allowedActions = $this->ROLE_ACTION_ACCESS[$this->role][$module] ?? [];
        $hasAccess = in_array($action, $allowedActions);

        $this->logAccessAttempt('action_check', "{$module}:{$action}", $hasAccess ? 'allowed' : 'denied');

        return $hasAccess;
    }

    /**
     * Check if user can access specific entity/resource
     */
    public function canAccessResource($module, $action, $employeeId, $currentUserId)
    {
        // First check module and action permissions
        if (!$this->hasActionAccess($module, $action)) {
            return false;
        }

        // Then check data scope
        $scope = $this->getDataScope($currentUserId);

        // Admin and system can access all
        if (in_array('all', $scope)) {
            return true;
        }

        // Check specific entity access based on scope
        if (in_array('self', $scope) && $employeeId != $currentUserId) {
            return false;
        }

        if (in_array('team', $scope)) {
            // Manager can access their team members
            return $this->isTeamMember($employeeId, $currentUserId);
        }

        if (in_array('department', $scope)) {
            // HR can access department members
            return $this->isDepartmentMember($employeeId, $currentUserId);
        }

        return $employeeId == $currentUserId;
    }

    /**
     * Get data scope for user
     */
    public function getDataScope($employeeId)
    {
        $scope = $this->DATA_SCOPE_RULES[$this->role] ?? ['self'];

        // If user has custom scope from permissions JSON
        if (isset($this->permissions['data_scope']) && is_array($this->permissions['data_scope'])) {
            $scope = $this->permissions['data_scope'];
        }

        return $scope;
    }

    /**
     * Mask sensitive fields in data array
     */
    public function maskSensitiveFields($data, $allowedFields = [])
    {
        if (!is_array($data)) {
            return $data;
        }

        $encryptor = new Encryptor();
        $masked = $data;

        foreach ($data as $field => $value) {
            // If field is in allowed list, don't mask
            if (!empty($allowedFields) && in_array($field, $allowedFields)) {
                continue;
            }

            // Check if field is sensitive
            if (isset(self::SENSITIVE_FIELDS[$field])) {
                $fieldType = self::SENSITIVE_FIELDS[$field];
                $masked[$field] = $encryptor->maskSensitiveData($value, $fieldType);
            }
        }

        return $masked;
    }

    /**
     * Get allowed read fields for user role
     */
    public function getAllowedReadFields($module, $isOwnData = false)
    {
        $fieldsConfig = [
            'personal-profile' => [
                'admin' => ['*'],
                'hr' => ['email', 'phone', 'first_name', 'last_name', 'date_of_birth', 'nationality'],
                'manager' => ['email', 'phone', 'first_name', 'last_name'],
                'employee' => ['first_name', 'last_name', 'email', 'phone'] // Own data only
            ],
            'job-organization' => [
                'admin' => ['*'],
                'hr' => ['*'],
                'manager' => ['*'],
                'employee' => ['designation', 'department', 'reporting_manager', 'employment_type']
            ]
        ];

        return $fieldsConfig[$module][$this->role] ?? [];
    }

    /**
     * Log access attempts for audit trail
     */
    public function logAccessAttempt($action, $resource, $status)
    {
        try {
            $this->AuditLogModel->insert([
                'user_id' => $this->userId,
                'employee_id' => null,
                'module' => 'permission',
                'action' => $action,
                'entity_type' => 'access_attempt',
                'entity_id' => $resource,
                'old_value' => null,
                'new_value' => $status,
                'change_reason' => 'Permission check',
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            log_message('error', 'Failed to log access attempt: ' . $e->getMessage());
        }
    }

    /**
     * Check if employee is part of user's team
     */
    private function isTeamMember($employeeId, $managerId)
    {
        try {
            // This would query from org_hierarchy or similar table
            // For now, return true (to be implemented with actual team structure)
            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to check team membership: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if employee is in same department as user
     */
    private function isDepartmentMember($employeeId, $hrUserId)
    {
        try {
            // This would query from employees table or similar
            // For now, return true (to be implemented with actual department structure)
            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to check department membership: ' . $e->getMessage());
            return false;
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

    /**
     * Get user's current role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set custom permissions (for testing or dynamic scenarios)
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Get all available modules for this user
     */
    public function getAvailableModules()
    {
        return $this->ROLE_MODULE_ACCESS[$this->role] ?? [];
    }

    /**
     * Check if role has admin privileges
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if role is HR
     */
    public function isHr()
    {
        return $this->role === 'hr';
    }

    /**
     * Check if role is Manager
     */
    public function isManager()
    {
        return $this->role === 'manager';
    }

    /**
     * Check if role is Employee
     */
    public function isEmployee()
    {
        return $this->role === 'employee';
    }
}
