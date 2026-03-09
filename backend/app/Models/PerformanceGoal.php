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

    protected array $casts = [
        'weightage' => '?float',
        'progress_percentage' => '?int',
        'achievement_percentage' => '?int',
    ];

    protected $validationRules = [
        'employee_id'   => 'required|integer',
        'goal_title'    => 'required|max_length[255]',
        'start_date'    => 'permit_empty|valid_date',
        'end_date'      => 'permit_empty|valid_date',
        'weightage'     => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        'status'        => 'permit_empty|in_list[Not Started,In Progress,Completed,Cancelled]',
        'progress_percentage'    => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        'achievement_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
    ];

    /**
     * Validate date ranges before insert/update
     */
    protected function initialize()
    {
        parent::initialize();
        $this->beforeInsert[] = 'validateDateRange';
        $this->beforeUpdate[] = 'validateDateRange';
    }

    protected function validateDateRange(array $data): array
    {
        $d = $data['data'] ?? $data;
        if (!empty($d['start_date']) && !empty($d['end_date'])) {
            if (strtotime($d['end_date']) < strtotime($d['start_date'])) {
                throw new \InvalidArgumentException('end_date must be after start_date');
            }
        }
        return $data;
    }

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
