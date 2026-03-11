<?php

namespace App\Models;

use CodeIgniter\Model;

class EpProject extends Model
{
    protected $table            = 'ep_projects';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'employee_id',
        'hrms_employee_id',
        'hrms_original_id',
        'source',
        'project_name',
        'description',
        'start_date',
        'end_date',
        'priority',
        'team_count',
        'project_live_link',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $validationRules = [
        'employee_id' => 'required|integer',
    ];

    /**
     * Get all project records for an employee.
     */
    public function getByEmployeeId(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->orderBy('start_date', 'DESC')
            ->findAll();
    }

    /**
     * Get HRMS IDs that have been overridden (EP copy exists, not soft-deleted).
     */
    public function getOverriddenHrmsIds(int $employeeId): array
    {
        return array_column(
            $this->select('hrms_original_id')
                ->where('employee_id', $employeeId)
                ->where('hrms_original_id IS NOT NULL')
                ->where('deleted_at IS NULL')
                ->findAll(),
            'hrms_original_id'
        );
    }

    /**
     * Get HRMS IDs that have been soft-deleted in EP (user chose to hide them).
     */
    public function getDeletedHrmsIds(int $employeeId): array
    {
        return array_column(
            $this->select('hrms_original_id')
                ->where('employee_id', $employeeId)
                ->where('hrms_original_id IS NOT NULL')
                ->where('deleted_at IS NOT NULL')
                ->withDeleted()
                ->findAll(),
            'hrms_original_id'
        );
    }
}
