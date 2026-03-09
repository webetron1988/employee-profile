<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\HrmsEmployee;
use App\Models\AuditLog;
use App\Models\SyncLog;
use App\Models\SystemConfiguration;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Admin extends Controller
{
    use ResponseTrait;

    protected $user;
    protected $hrmsEmployee;
    protected $auditLog;
    protected $syncLog;
    protected $configuration;

    public function __construct()
    {
        $this->user = new User();
        $this->hrmsEmployee = new HrmsEmployee();
        $this->auditLog = new AuditLog();
        $this->syncLog = new SyncLog();
        $this->configuration = new SystemConfiguration();
    }

    /**
     * List all employees (HR/Admin only)
     * GET /admin/employees
     * Now queries users table directly (hrms_employee_id on users).
     */
    public function listEmployees()
    {
        try {
            $page = $this->request->getVar('page') ?? 1;
            $perPage = $this->request->getVar('per_page') ?? 20;
            $search = $this->request->getVar('search');

            $query = $this->user;

            if ($search) {
                $query = $query->groupStart()
                    ->like('first_name', $search)
                    ->orLike('last_name', $search)
                    ->orLike('email', $search)
                    ->orLike('hrms_employee_id', $search)
                    ->groupEnd();
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
     * Create new employee (creates user with hrms_employee_id)
     * POST /admin/employees
     */
    public function createEmployee()
    {
        try {
            $data = $this->request->getJSON(true);

            if (isset($data['password'])) {
                $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                unset($data['password']);
            }

            if ($this->user->insert($data)) {
                $this->logAudit('EMPLOYEE_CREATED', $data['email'] ?? 'Unknown', 'Created new employee/user');
                return $this->respond(['message' => 'Employee created successfully'], 201);
            }

            return $this->fail($this->user->errors(), 422);
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
            $user = $this->user->find($id);

            if (!$user) {
                return $this->failNotFound('Employee not found');
            }

            $data = $this->request->getJSON(true);

            if ($this->user->update($id, $data)) {
                $this->logAudit('EMPLOYEE_UPDATED', $user['email'], 'Updated employee information');
                return $this->respond(['message' => 'Employee updated'], 200);
            }

            return $this->fail($this->user->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating employee');
        }
    }

    /**
     * Delete employee (deactivate user)
     * DELETE /admin/employees/{id}
     */
    public function deleteEmployee($id)
    {
        try {
            $user = $this->user->find($id);

            if (!$user) {
                return $this->failNotFound('Employee not found');
            }

            // Deactivate instead of hard delete
            if ($this->user->update($id, ['is_active' => 0])) {
                $this->logAudit('EMPLOYEE_DELETED', $user['email'], 'Deactivated employee record');
                return $this->respond(['message' => 'Employee deactivated'], 200);
            }

            return $this->failServerError('Error deactivating employee');
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
     * Sync users from HRMS database directly (hrms_employee_id on users).
     * POST /admin/sync/employees
     */
    public function syncEmployees()
    {
        try {
            $startTime = date('Y-m-d H:i:s');
            $logId = $this->syncLog->insert([
                'sync_type'  => 'employee_master',
                'sync_date'  => $startTime,
                'status'     => 'In Progress',
            ], true);

            // Read directly from HRMS database
            $hrmsRows = $this->hrmsEmployee->getAllActive();

            $created = 0;
            $updated = 0;
            $failed = 0;
            $errors = [];

            foreach ($hrmsRows as $hrmsRow) {
                try {
                    $userData = $this->hrmsEmployee->formatForEp($hrmsRow);

                    // Look up by hrms_employee_id first, then email
                    $existingUser = $this->user
                        ->where('hrms_employee_id', (string) $hrmsRow['empID'])
                        ->first();

                    if (!$existingUser) {
                        $existingUser = $this->user->where('email', $hrmsRow['email'])->first();
                    }

                    if ($existingUser) {
                        $userData['updated_at'] = date('Y-m-d H:i:s');
                        $this->user->skipValidation(true)->update($existingUser['id'], $userData);
                        $updated++;
                    } else {
                        $userData['password_hash'] = null;
                        $userData['permissions']   = json_encode([]);
                        $userData['created_at']    = date('Y-m-d H:i:s');
                        $userData['updated_at']    = date('Y-m-d H:i:s');
                        $this->user->skipValidation(true)->insert($userData);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = ($hrmsRow['empID'] ?? '?') . ': ' . $e->getMessage();
                }
            }

            $endTime = date('Y-m-d H:i:s');

            $this->syncLog->update($logId, [
                'status'            => $failed > 0 ? 'Completed with Errors' : 'Completed',
                'records_processed' => count($hrmsRows),
                'records_created'   => $created,
                'records_updated'   => $updated,
                'records_failed'    => $failed,
                'error_details'     => !empty($errors) ? json_encode($errors) : null,
                'completed_at'      => $endTime,
            ]);

            $this->logAudit('EMPLOYEE_SYNC', 'System', "Synced from HRMS DB: {$created} created, {$updated} updated, {$failed} failed");

            return $this->respond([
                'status'  => 'success',
                'message' => 'Sync completed',
                'data'    => [
                    'hrms_total' => count($hrmsRows),
                    'created'    => $created,
                    'updated'    => $updated,
                    'failed'     => $failed,
                    'errors'     => $errors,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error syncing employees: ' . $e->getMessage());
        }
    }

    /**
     * Compare EP users vs HRMS employees.
     * GET /admin/users/hrms-compare
     */
    public function usersHrmsCompare()
    {
        try {
            $epUsers = $this->user->findAll();
            $hrmsRows = $this->hrmsEmployee->getAllActive();

            // Index EP users by hrms_employee_id
            $epByHrms = [];
            foreach ($epUsers as $u) {
                if (!empty($u['hrms_employee_id'])) {
                    $epByHrms[$u['hrms_employee_id']] = $u;
                }
            }

            // Build comparison
            $comparison = [];
            $hrmsEmails = [];

            foreach ($hrmsRows as $hrmsRow) {
                $hrmsEmails[$hrmsRow['email']] = true;
                $epUser = $epByHrms[(string) $hrmsRow['empID']] ?? null;

                // Fallback: match by email
                if (!$epUser) {
                    foreach ($epUsers as $u) {
                        if ($u['email'] === $hrmsRow['email']) {
                            $epUser = $u;
                            break;
                        }
                    }
                }

                $comparison[] = [
                    'hrms_emp_id' => $hrmsRow['empID'],
                    'hrms_uid'    => $hrmsRow['uid'],
                    'hrms_name'   => trim(($hrmsRow['name'] ?? '') . ' ' . ($hrmsRow['last_name'] ?? '')),
                    'email'       => $hrmsRow['email'],
                    'hrms_role'   => $hrmsRow['role_role_name'] ?? $hrmsRow['user_type'],
                    'hrms_status' => $hrmsRow['status'],
                    'ep_user'     => $epUser ? 'yes' : 'no',
                    'ep_role'     => $epUser['role'] ?? null,
                    'ep_active'   => $epUser ? (bool) $epUser['is_active'] : null,
                    'synced'      => $epUser ? true : false,
                ];
            }

            // Find EP users not in HRMS (orphans)
            $orphans = [];
            foreach ($epUsers as $u) {
                if (!isset($hrmsEmails[$u['email']])) {
                    $orphans[] = [
                        'ep_user_id'       => $u['id'],
                        'email'            => $u['email'],
                        'hrms_employee_id' => $u['hrms_employee_id'] ?? null,
                        'role'             => $u['role'],
                        'is_active'        => (bool) $u['is_active'],
                    ];
                }
            }

            return $this->respond([
                'status' => 'success',
                'data'   => [
                    'hrms_total'      => count($hrmsRows),
                    'ep_users'        => count($epUsers),
                    'synced'          => count(array_filter($comparison, fn($c) => $c['synced'])),
                    'not_synced'      => count(array_filter($comparison, fn($c) => !$c['synced'])),
                    'orphan_ep_users' => count($orphans),
                    'comparison'      => $comparison,
                    'orphans'         => $orphans,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error comparing users: ' . $e->getMessage());
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
            $page       = (int)($this->request->getVar('page') ?? 1);
            $perPage    = (int)($this->request->getVar('per_page') ?? 50);
            $action     = $this->request->getVar('action');
            $entityType = $this->request->getVar('entity_type');
            $userId     = $this->request->getVar('user_id');
            $status     = $this->request->getVar('status');
            $ipAddress  = $this->request->getVar('ip_address');
            $dateFrom   = $this->request->getVar('date_from');
            $dateTo     = $this->request->getVar('date_to');
            $search     = $this->request->getVar('search');

            $query = $this->auditLog;

            if ($action) {
                $query = $query->where('action', $action);
            }
            if ($entityType) {
                $query = $query->where('entity_type', $entityType);
            }
            if ($userId) {
                $query = $query->where('user_id', (int)$userId);
            }
            if ($status) {
                $query = $query->where('status', $status);
            }
            if ($ipAddress) {
                $query = $query->where('ip_address', $ipAddress);
            }
            if ($dateFrom) {
                $query = $query->where('created_at >=', $dateFrom . ' 00:00:00');
            }
            if ($dateTo) {
                $query = $query->where('created_at <=', $dateTo . ' 23:59:59');
            }
            if ($search) {
                $query = $query->groupStart()
                    ->like('change_reason', $search)
                    ->orLike('action', $search)
                    ->orLike('entity_type', $search)
                    ->groupEnd();
            }

            $total = $query->countAllResults(false);
            $logs = $query->orderBy('created_at', 'DESC')->paginate($perPage);

            return $this->respond([
                'data' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'total_pages'  => ceil($total / $perPage),
                ]
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching audit logs');
        }
    }

    /**
     * Export audit logs as CSV
     * GET /admin/audit-logs/export
     */
    public function exportAuditLogs()
    {
        try {
            $action     = $this->request->getVar('action');
            $entityType = $this->request->getVar('entity_type');
            $userId     = $this->request->getVar('user_id');
            $dateFrom   = $this->request->getVar('date_from');
            $dateTo     = $this->request->getVar('date_to');

            $query = $this->auditLog;

            if ($action) {
                $query = $query->where('action', $action);
            }
            if ($entityType) {
                $query = $query->where('entity_type', $entityType);
            }
            if ($userId) {
                $query = $query->where('user_id', (int)$userId);
            }
            if ($dateFrom) {
                $query = $query->where('created_at >=', $dateFrom . ' 00:00:00');
            }
            if ($dateTo) {
                $query = $query->where('created_at <=', $dateTo . ' 23:59:59');
            }

            $logs = $query->orderBy('created_at', 'DESC')->limit(10000)->findAll();

            // Build CSV
            $csv = "ID,User ID,Employee ID,Module,Action,Entity Type,Entity ID,Status,IP Address,Change Reason,Created At\n";
            foreach ($logs as $log) {
                $csv .= implode(',', [
                    $log['id'] ?? '',
                    $log['user_id'] ?? '',
                    $log['employee_id'] ?? '',
                    '"' . str_replace('"', '""', $log['module'] ?? '') . '"',
                    '"' . str_replace('"', '""', $log['action'] ?? '') . '"',
                    '"' . str_replace('"', '""', $log['entity_type'] ?? '') . '"',
                    $log['entity_id'] ?? '',
                    '"' . str_replace('"', '""', $log['status'] ?? '') . '"',
                    $log['ip_address'] ?? '',
                    '"' . str_replace('"', '""', $log['change_reason'] ?? '') . '"',
                    $log['created_at'] ?? '',
                ]) . "\n";
            }

            return $this->response
                ->setHeader('Content-Type', 'text/csv')
                ->setHeader('Content-Disposition', 'attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"')
                ->setBody($csv);
        } catch (\Throwable $e) {
            return $this->failServerError('Error exporting audit logs');
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
