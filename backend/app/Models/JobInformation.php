<?php

namespace App\Models;

use CodeIgniter\Model;

class JobInformation extends Model
{
    protected $table = 'job_information';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'designation',
        'department',
        'grade',
        'job_level',
        'employment_type',
        'employment_status',
        'salary_grade',
        'joined_date',
        'confirmation_date',
        'reporting_manager_id',
        'reporting_since',
        'functional_manager_id',
        'matrix_relationship',
        'location',
        'cost_center',
        'business_unit',
        'work_schedule',
        'weekly_hours',
        'fte',
        'union_member',
        'contract_end_date',
        'budget_authority',
        'signing_authority',
        'cost_centre_name',
        'gl_code',
        'flsa_status',
        'eeo_category',
        'job_family',
        'job_sub_family',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
    ];

    protected $useSoftDeletes = true;

    protected $validationRules = [
        'employee_id' => 'required',
        'designation' => 'permit_empty|string|max_length[100]',
        'department' => 'permit_empty|string|max_length[100]',
        'employment_type' => 'permit_empty|in_list[Full-Time,Part-Time,Contract,Temporary,Intern]',
        'flsa_status' => 'permit_empty|max_length[50]',
        'eeo_category' => 'permit_empty|max_length[100]',
        'job_family' => 'permit_empty|max_length[100]',
        'job_sub_family' => 'permit_empty|max_length[100]',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id', 'id');
    }

    public function functionalManager()
    {
        return $this->belongsTo(Employee::class, 'functional_manager_id', 'id');
    }
}
