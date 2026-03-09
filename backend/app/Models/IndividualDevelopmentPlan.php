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
        'timeline',
        'readiness_level',
        'preferred_track',
        'geographic_preference',
        'functional_interest',
        'international_assignment',
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

    protected array $casts = [
        'plan_year' => '?int',
    ];

    protected $validationRules = [
        'employee_id' => 'required|integer',
        'career_goal' => 'required|max_length[2000]',
        'plan_year'   => 'permit_empty|integer|greater_than_equal_to[2020]|less_than_equal_to[2035]',
        'status'      => 'permit_empty|in_list[Draft,In Progress,Completed,Postponed,Active,Cancelled]',
        'reviewed_date' => 'permit_empty|valid_date',
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
