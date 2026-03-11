<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HrmsProject — Read-only model for HRMS `employee_projects` table.
 * Uses the `hrms` database connection to query project data directly
 * from the HRMS system.
 */
class HrmsProject extends Model
{
    protected $DBGroup    = 'hrms';
    protected $table      = 'employee_projects';
    protected $primaryKey = 'project_id';
    protected $returnType = 'array';

    protected $allowedFields = []; // read-only — no inserts/updates from EP

    /**
     * Fetch all project records for an employee.
     */
    public function getByEmpId(int $empId): array
    {
        return $this->db->table('employee_projects')
            ->select('project_id, project_name, description, start_date, end_date, priority, team_count, project_live_link')
            ->where('emp_id', $empId)
            ->orderBy('start_date', 'DESC')
            ->get()
            ->getResultArray();
    }
}
