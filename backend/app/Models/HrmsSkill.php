<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HrmsSkill — Read-only model for HRMS `employee_skills` table.
 * Uses the `hrms` database connection to query skill data directly
 * from the HRMS system.
 */
class HrmsSkill extends Model
{
    protected $DBGroup    = 'hrms';
    protected $table      = 'employee_skills';
    protected $primaryKey = 'emp_skill_id';
    protected $returnType = 'array';

    protected $allowedFields = []; // read-only — no inserts/updates from EP

    /**
     * Fetch all skill records for an employee.
     */
    public function getByEmpId(int $empId): array
    {
        return $this->db->table('employee_skills')
            ->select('emp_skill_id, emp_skill_title, skill_proficiency_lvl_label, skill_proficiency_level, years_of_experience, last_used_date, skill_ref_type, acquisition_source, certificate_validity')
            ->where('emp_id', $empId)
            ->whereIn('status', ['A', 'U'])
            ->orderBy('skill_proficiency_level', 'DESC')
            ->get()
            ->getResultArray();
    }
}
