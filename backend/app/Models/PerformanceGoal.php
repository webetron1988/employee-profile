<?php

namespace App\Models;

use CodeIgniter\Model;

class PerformanceGoal extends Model
{
    protected $table = 'performance_goals';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'goal_title',
        'goal_description',
        'goal_category',
        'start_date',
        'end_date',
        'target_value',
        'measurement_criteria',
        'weightage',
        'status',
        'progress_percentage',
        'achievement_percentage',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'weightage' => 'float',
        'progress_percentage' => 'int',
        'achievement_percentage' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
