<?php

namespace App\Models;

use CodeIgniter\Model;

class PerformanceFeedback extends Model
{
    protected $table = 'performance_feedback';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'reviewer_id',
        'feedback_type',
        'feedback_period',
        'strengths',
        'areas_for_improvement',
        'suggestions',
        'overall_comment',
        'rating',
        'is_anonymous',
        'status',
        'shared_date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'shared_date' => 'date'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Employee::class, 'reviewer_id', 'id');
    }
}
