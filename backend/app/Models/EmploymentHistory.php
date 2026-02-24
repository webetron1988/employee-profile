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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
