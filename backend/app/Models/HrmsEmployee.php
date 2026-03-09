<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HrmsEmployee — Read-only model for HRMS `employee` table.
 * Uses the `hrms` database connection to query employee data directly
 * from the HRMS system for syncing into the Employee Profile DB.
 */
class HrmsEmployee extends Model
{
    protected $DBGroup    = 'hrms';
    protected $table      = 'employee';
    protected $primaryKey = 'empID';
    protected $returnType = 'array';

    protected $allowedFields = []; // read-only — no inserts/updates from EP

    /**
     * Map HRMS role_code to EP role enum.
     */
    private const ROLE_MAP = [
        'SPRA'  => 'admin',
        'ADMN'  => 'admin',
        'SYSTM' => 'system',
        'MNGR'  => 'manager',
        'SPRVR' => 'manager',
        'SFKD3' => 'hr',       // HR Admin
        '1ELAC' => 'hr',       // HR Manager
        'CLADM' => 'admin',    // Client Admin
    ];

    /**
     * Fetch a single employee by empID with role info.
     */
    public function getByEmpId(int $empID): ?array
    {
        $row = $this->db->table('employee e')
            ->select('e.empID, e.uid, e.name, e.middle_name, e.last_name, e.email,
                      e.contact, e.dob, e.gender, e.nationality, e.profile,
                      e.role_id, e.user_type, e.status, e.client_id,
                      e.date_of_joining, e.position_id, e.job_id,
                      e.marital_status, e.blood_group, e.address,
                      r.role_role_code, r.role_role_name')
            ->join('roles_master r', 'r.role_role_id = e.role_id', 'left')
            ->where('e.empID', $empID)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Fetch a single employee by email.
     */
    public function getByEmail(string $email): ?array
    {
        $row = $this->db->table('employee e')
            ->select('e.empID, e.uid, e.name, e.middle_name, e.last_name, e.email,
                      e.contact, e.dob, e.gender, e.nationality, e.profile,
                      e.role_id, e.user_type, e.status, e.client_id,
                      e.date_of_joining, e.position_id, e.job_id,
                      e.marital_status, e.blood_group, e.address,
                      r.role_role_code, r.role_role_name')
            ->join('roles_master r', 'r.role_role_id = e.role_id', 'left')
            ->where('e.email', $email)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Fetch all active employees (for bulk sync).
     */
    public function getAllActive(int $clientId = 0): array
    {
        $builder = $this->db->table('employee e')
            ->select('e.empID, e.uid, e.name, e.middle_name, e.last_name, e.email,
                      e.contact, e.dob, e.gender, e.nationality, e.profile,
                      e.role_id, e.user_type, e.status, e.client_id,
                      e.date_of_joining, e.position_id, e.job_id,
                      r.role_role_code, r.role_role_name')
            ->join('roles_master r', 'r.role_role_id = e.role_id', 'left')
            ->where('e.status', 'active');

        if ($clientId > 0) {
            $builder->where('e.client_id', $clientId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Convert a raw HRMS employee row into the format needed by EP's
     * `users` table (hrms_employee_id stored directly on users).
     *
     * @return array  User data ready for insert/update
     */
    public function formatForEp(array $hrmsRow): array
    {
        $epRole = self::ROLE_MAP[$hrmsRow['role_role_code'] ?? ''] ?? 'employee';

        // Map HRMS status → EP status
        $statusMap = [
            'active'     => 'active',
            'inactive'   => 'inactive',
            'terminated' => 'inactive',
            'exited'     => 'inactive',
            'absconded'  => 'suspended',
            'deleted'    => 'inactive',
            'pending'    => 'inactive',
            'promoted'   => 'active',
        ];
        $epStatus = $statusMap[$hrmsRow['status'] ?? 'pending'] ?? 'inactive';

        return [
            'hrms_employee_id'    => (string) $hrmsRow['empID'],
            'email'               => $hrmsRow['email'],
            'first_name'          => $hrmsRow['name'] ?? '',
            'last_name'           => $hrmsRow['last_name'] ?? '',
            'phone'               => $hrmsRow['contact'] ?: null,
            'profile_picture_url' => $hrmsRow['profile'] ?: null,
            'role'                => $epRole,
            'is_active'           => ($epStatus === 'active') ? 1 : 0,
        ];
    }
}
