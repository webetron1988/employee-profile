<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeCompetency extends Model
{
    protected $table = 'employee_competencies';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'competency_id',
        'proficiency_level',
        'self_assessment',
        'manager_assessment',
        'development_goal',
        'assessment_date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'assessment_date' => 'date'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class, 'competency_id', 'id');
    }
}
