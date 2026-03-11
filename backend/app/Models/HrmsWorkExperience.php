<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HrmsWorkExperience — Read-only model for HRMS `employee_work_experience` table.
 * Uses the `hrms` database connection to query work experience data directly
 * from the HRMS system.
 */
class HrmsWorkExperience extends Model
{
    protected $DBGroup    = 'hrms';
    protected $table      = 'employee_work_experience';
    protected $primaryKey = 'experience_id';
    protected $returnType = 'array';

    protected $allowedFields = []; // read-only — no inserts/updates from EP

    /**
     * Fetch all work experience records for an employee.
     */
    public function getByEmpId(int $empId): array
    {
        return $this->db->table('employee_work_experience')
            ->select('experience_id, company_name, location, designation, description, start_date, end_date, is_current')
            ->where('emp_id', $empId)
            ->where('is_deleted', 0)
            ->orderBy('start_date', 'DESC')
            ->get()
            ->getResultArray();
    }
}
