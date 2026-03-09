<?php

namespace App\Models;

use CodeIgniter\Model;

class EmploymentHistory extends Model
{
    protected $table = 'employment_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'designation',
        'department',
        'grade',
        'reporting_manager_id',
        'location',
        'employment_type',
        'start_date',
        'end_date',
        'reason_for_change',
        'approval_status',
        'comments',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
    ];

    protected $validationRules = [
        'employee_id'      => 'required|integer',
        'designation'      => 'permit_empty|max_length[255]',
        'department'       => 'permit_empty|max_length[255]',
        'start_date'       => 'permit_empty|valid_date',
        'end_date'         => 'permit_empty|valid_date',
        'employment_type'  => 'permit_empty|in_list[Full-time,Part-time,Contract,Intern]',
    ];

    /**
     * Validate date ranges before insert/update
     */
    protected function initialize()
    {
        parent::initialize();
        $this->beforeInsert[] = 'validateDateRange';
        $this->beforeUpdate[] = 'validateDateRange';
    }

    protected function validateDateRange(array $data): array
    {
        $d = $data['data'] ?? $data;
        if (!empty($d['start_date']) && !empty($d['end_date'])) {
            if (strtotime($d['end_date']) < strtotime($d['start_date'])) {
                throw new \InvalidArgumentException('end_date must be after start_date');
            }
        }
        return $data;
    }

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
