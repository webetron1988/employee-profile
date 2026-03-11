<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HrmsEducation — Read-only model for HRMS `employee_education` table.
 * Uses the `hrms` database connection to query education data directly
 * from the HRMS system.
 */
class HrmsEducation extends Model
{
    protected $DBGroup    = 'hrms';
    protected $table      = 'employee_education';
    protected $primaryKey = 'education_id';
    protected $returnType = 'array';

    protected $allowedFields = []; // read-only — no inserts/updates from EP

    /**
     * Fetch all education records for an employee.
     */
    public function getByEmpId(int $empId): array
    {
        return $this->db->table('employee_education')
            ->select('education_id, degree_name, field_of_study, college_name, start_year, end_year, grade, honors, thesis')
            ->where('emp_id', $empId)
            ->where('is_deleted', 0)
            ->orderBy('end_year', 'DESC')
            ->get()
            ->getResultArray();
    }
}
