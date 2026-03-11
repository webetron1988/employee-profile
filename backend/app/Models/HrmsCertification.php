<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HrmsCertification — Read-only model for HRMS `employee_certifications` table.
 * Uses the `hrms` database connection to query certification data directly
 * from the HRMS system.
 */
class HrmsCertification extends Model
{
    protected $DBGroup    = 'hrms';
    protected $table      = 'employee_certifications';
    protected $primaryKey = 'certification_id';
    protected $returnType = 'array';

    protected $allowedFields = []; // read-only — no inserts/updates from EP

    /**
     * Fetch all certification records for an employee.
     */
    public function getByEmpId(int $empId): array
    {
        return $this->db->table('employee_certifications')
            ->select('certification_id, certificate_name, start_date, end_date, grade, cost')
            ->where('emp_id', $empId)
            ->where('approval_status !=', 'E')
            ->orderBy('start_date', 'DESC')
            ->get()
            ->getResultArray();
    }
}
