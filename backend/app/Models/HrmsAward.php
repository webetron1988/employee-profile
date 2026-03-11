<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HrmsAward — Read-only model for HRMS `employee_awards` table.
 * Uses the `hrms` database connection to query award data directly
 * from the HRMS system.
 */
class HrmsAward extends Model
{
    protected $DBGroup    = 'hrms';
    protected $table      = 'employee_awards';
    protected $primaryKey = 'award_id';
    protected $returnType = 'array';

    protected $allowedFields = []; // read-only — no inserts/updates from EP

    /**
     * Fetch all award records for an employee.
     */
    public function getByEmpId(int $empId): array
    {
        return $this->db->table('employee_awards')
            ->select('award_id, award_name, award_date, description, award_type, awarded_by, reward_amount')
            ->where('emp_id', $empId)
            ->where('approval_status !=', 'E')
            ->orderBy('award_date', 'DESC')
            ->get()
            ->getResultArray();
    }
}
