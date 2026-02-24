<?php

namespace App\Models;

use CodeIgniter\Model;

class IndividualDevelopmentPlan extends Model
{
    protected $table = 'individual_development_plan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'plan_year',
        'career_goal',
        'skill_gaps',
        'development_activities',
        'training_needs',
        'mentor_assigned_id',
        'status',
        'reviewed_by_id',
        'reviewed_date',
        'comments',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'plan_year' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reviewed_date' => 'date'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function mentor()
    {
        return $this->belongsTo(Employee::class, 'mentor_assigned_id', 'id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id', 'id');
    }
}
