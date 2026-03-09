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

    protected array $casts = [
        'is_anonymous' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'     => 'required|integer',
        'feedback_type'   => 'permit_empty|in_list[Self,Peer,Manager,360,360 Degree,Check-in]',
        'feedback_period' => 'permit_empty|max_length[100]',
        'rating'          => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[5]',
        'status'          => 'permit_empty|max_length[50]',
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
