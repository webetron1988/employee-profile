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

    protected array $casts = [
        'overall_rating' => '?float',
    ];

    protected $validationRules = [
        'employee_id'    => 'required|integer',
        'review_period'  => 'permit_empty|max_length[100]',
        'review_date'    => 'permit_empty|valid_date',
        'overall_rating' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[5]',
        'performance_status' => 'permit_empty|in_list[Pending,In Progress,Completed,Cancelled]',
        'approval_status'    => 'permit_empty|in_list[Pending,Approved,Rejected]',
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
