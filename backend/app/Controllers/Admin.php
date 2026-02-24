<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\SyncLog;
use App\Models\SystemConfiguration;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Admin extends Controller
{
    use ResponseTrait;

    protected $employee;
    protected $user;
    protected $auditLog;
    protected $syncLog;
    protected $configuration;

    public function __construct()
    {
        $this->employee = new Employee();
        $this->user = new User();
        $this->auditLog = new AuditLog();
        $this->syncLog = new SyncLog();
        $this->configuration = new SystemConfiguration();
    }

    /**
     * List all employees (HR/Admin only)
     * GET /admin/employees
     */
    public function listEmployees()
    {
        try {
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('per_page') ?? 20;
            $search = $this->request->getVar('search');
            $department = $this->request->getVar('department');

            $query = $this->employee;

            if ($search) {
                $query = $query->groupStart()
                    ->like('first_name', $search)
                    ->orLike('last_name', $search)
                    ->orLike('email', $search)
                    ->orLike('hrms_employee_id', $search)
                    ->groupEnd();
            }

            if ($department) {
                // Would need to join with job_information table
                // Simplified for now
            }

            $total = $query->countAllResults(false);
            $employees = $query->paginate($perPage);

            return $this->respond([
                'data' => $employees,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'pages' => ceil($total / $perPage)
                ]
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error listing employees');
        }
    }

    /**
     * Create new employee
     * POST /admin/employees
     */
    public function createEmployee()
    {
        try {
            $data = $this->request->getJSON(true);

            if ($this->employee->insert($data)) {
                // Log audit
                $this->logAudit('EMPLOYEE_CREATED', $data['email'] ?? 'Unknown', 'Created new employee');

                return $this->respond(['message' => 'Employee created successfully'], 201);
            }

            return $this->fail($this->employee->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating employee');
        }
    }

    /**
     * Update employee
     * PUT /admin/employees/{id}
     */
    public function updateEmployee($id)
    {
        try {
            $employee = $this->employee->find($id);

            if (!$employee) {
                return $this->failNotFound('Employee not found');
            }

            $data = $this->request->getJSON(true);

            if ($this->employee->update($id, $data)) {
                $this->logAudit('EMPLOYEE_UPDATED', $employee['email'], 'Updated employee information');

                return $this->respond(['message' => 'Employee updated'], 200);
            }

            return $this->fail($this->employee->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating employee');
        }
    }

    /**
     * Delete employee (soft delete)
     * DELETE /admin/employees/{id}
     */
    public function deleteEmployee($id)
    {
        try {
            $employee = $this->employee->find($id);

            if (!$employee) {
                return $this->failNotFound('Employee not found');
            }

            if ($this->employee->delete($id)) {
                $this->logAudit('EMPLOYEE_DELETED', $employee['email'], 'Deleted employee record');

                return $this->respond(['message' => 'Employee deleted'], 200);
            }

            return $this->failServerError('Error deleting employee');
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting employee');
        }
    }

    /**
     * List all users
     * GET /admin/users
     */
    public function listUsers()
    {
        try {
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('per_page') ?? 20;

            $total = $this->user->countAllResults(false);
            $users = $this->user->paginate($perPage);

            return $this->respond([
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total
                ]
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error listing users');
        }
    }

    /**
     * Create new user
     * POST /admin/users
     */
    public function createUser()
    {
        try {
            $data = $this->request->getJSON(true);

            // Hash password
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            if ($this->user->insert($data)) {
                $this->logAudit('USER_CREATED', $data['email'] ?? 'Unknown', 'Created new user');

                return $this->respond(['message' => 'User created'], 201);
            }

            return $this->fail($this->user->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating user');
        }
    }

    /**
     * Update user
     * PUT /admin/users/{id}
     */
    public function updateUser($id)
    {
        try {
            $user = $this->user->find($id);

            if (!$user) {
                return $this->failNotFound('User not found');
            }

            $data = $this->request->getJSON(true);

            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            if ($this->user->update($id, $data)) {
                $this->logAudit('USER_UPDATED', $user['email'], 'Updated user information');

                return $this->respond(['message' => 'User updated'], 200);
            }

            return $this->fail($this->user->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating user');
        }
    }

    /**
     * Sync employees from HRMS
     * POST /admin/sync/employees
     */
    public function syncEmployees()
    {
        try {
            $syncType = $this->request->getVar('type') ?? 'full';
            $syncLog = [
                'sync_type' => 'employee_master',
                'sync_date' => date('Y-m-d H:i:s'),
                'status' => 'In Progress'
            ];

            $logId = $this->syncLog->insert($syncLog, true);

            // Call HRMS client to fetch employees
            $hrmsClient = service('hrmsclient');
            $employees = $hrmsClient->getEmployees($syncType);

            $synced = 0;
            $failed = 0;
            $errors = [];

            foreach ($employees as $emp) {
                try {
                    $existing = $this->employee->where('hrms_employee_id', $emp['id'])->first();

                    if ($existing) {
                        $this->employee->update($existing['id'], $emp);
                    } else {
                        $this->employee->insert($emp);
                    }

                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = $emp['id'] ?? 'Unknown';
                }
            }

            // Update sync log
            $this->syncLog->update($logId, [
                'status' => 'Completed',
                'records_processed' => $synced,
                'records_failed' => $failed,
                'error_details' => !empty($errors) ? implode(',', $errors) : null,
                'completed_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit('EMPLOYEE_SYNC', 'System', "Synced $synced employees");

            return $this->respond([
                'message' => 'Sync completed',
                'synced' => $synced,
                'failed' => $failed
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error syncing employees: ' . $e->getMessage());
        }
    }

    /**
     * Get sync status
     * GET /admin/sync/status
     */
    public function getSyncStatus()
    {
        try {
            $latestSync = $this->syncLog
                ->orderBy('sync_date', 'DESC')
                ->first();

            return $this->respond(['data' => $latestSync], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching sync status');
        }
    }

    /**
     * Get sync logs
     * GET /admin/sync/logs
     */
    public function getSyncLogs()
    {
        try {
            $limit = $this->request->getVar('limit') ?? 50;

            $logs = $this->syncLog
                ->orderBy('sync_date', 'DESC')
                ->limit($limit)
                ->findAll();

            return $this->respond(['data' => $logs], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching sync logs');
        }
    }

    /**
     * Get audit logs
     * GET /admin/audit-logs
     */
    public function getAuditLogs()
    {
        try {
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('per_page') ?? 50;
            $action = $this->request->getVar('action');

            $query = $this->auditLog;

            if ($action) {
                $query = $query->where('action', $action);
            }

            $total = $query->countAllResults(false);
            $logs = $query->orderBy('created_at', 'DESC')->paginate($perPage);

            return $this->respond([
                'data' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total
                ]
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching audit logs');
        }
    }

    /**
     * Get specific audit log
     * GET /admin/audit-logs/{id}
     */
    public function getAuditLogId($id)
    {
        try {
            $log = $this->auditLog->find($id);

            if (!$log) {
                return $this->failNotFound('Audit log not found');
            }

            return $this->respond(['data' => $log], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching audit log');
        }
    }

    /**
     * Get system configuration
     * GET /admin/configuration
     */
    public function getConfiguration()
    {
        try {
            $configs = $this->configuration->findAll();

            $result = [];
            foreach ($configs as $config) {
                $result[$config['config_key']] = $config['config_value'];
            }

            return $this->respond(['data' => $result], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching configuration');
        }
    }

    /**
     * Update system configuration
     * PUT /admin/configuration/{key}
     */
    public function updateConfiguration($key)
    {
        try {
            $data = $this->request->getJSON(true);

            $config = $this->configuration->where('config_key', $key)->first();

            if ($config) {
                $this->configuration->update($config['id'], ['config_value' => $data['config_value'] ?? '']);
            } else {
                $this->configuration->insert([
                    'config_key' => $key,
                    'config_value' => $data['config_value'] ?? '',
                    'config_type' => $data['config_type'] ?? 'String'
                ]);
            }

            $this->logAudit('CONFIG_UPDATED', 'System', "Updated configuration key: $key");

            return $this->respond(['message' => 'Configuration updated'], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating configuration');
        }
    }

    /**
     * Helper function to log audit entries
     */
    protected function logAudit($action, $subject, $description)
    {
        try {
            $this->auditLog->insert([
                'user_id' => auth()->user()?->id,
                'action' => $action,
                'subject' => $subject,
                'description' => $description,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()
            ]);
        } catch (\Throwable $e) {
            // Silent fail - don't interrupt main operation
            log_message('error', 'Failed to log audit: ' . $e->getMessage());
        }
    }
}
