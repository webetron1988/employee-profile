<?php

namespace App\Models;

use CodeIgniter\Model;

class SuccessionPlan extends Model
{
    protected $table = 'succession_plans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'employee_id',
        'plan_type',
        'successor_name',
        'successor_title',
        'target_position',
        'target_holder_name',
        'readiness',
        'readiness_percentage',
        'critical_experiences',
        'gaps',
        'development_plan',
        'strengths',
        'develop_areas',
        'classification',
        'review_date',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $validationRules = [
        'employee_id' => 'required|integer',
        'plan_type'   => 'required|in_list[emergency_successor,can_succeed_into,bench_strength]',
        'readiness_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
    ];
}
