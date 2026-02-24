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
        'functional_manager_id',
        'location',
        'cost_center',
        'business_unit',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'joined_date' => 'date',
        'confirmation_date' => 'date'
    ];

    protected $useSoftDeletes = true;

    protected $validationRules = [
        'employee_id' => 'required',
        'designation' => 'required|string|max_length[100]',
        'department' => 'required|string|max_length[100]',
        'employment_type' => 'required|in_list[Full-Time,Part-Time,Contract,Temporary,Intern]'
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
