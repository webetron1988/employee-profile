<?php

namespace App\Models;

use CodeIgniter\Model;

class PerformanceReview extends Model
{
    protected $table = 'performance_reviews';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'reviewer_id',
        'review_period',
        'review_date',
        'overall_rating',
        'performance_status',
        'strengths',
        'areas_for_improvement',
        'goals_met',
        'comments',
        'approval_status',
        'approved_by_id',
        'approved_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'overall_rating' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'review_date' => 'date',
        'approved_at' => 'datetime'
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_id', 'id');
    }
}
