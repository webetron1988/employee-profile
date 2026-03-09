<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseEnrollment extends Model
{
    protected $table = 'course_enrollments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'course_id',
        'enrollment_date',
        'scheduled_start_date',
        'scheduled_end_date',
        'actual_start_date',
        'actual_end_date',
        'completion_status',
        'completion_percentage',
        'score',
        'passing_score',
        'passed',
        'certificate_url',
        'notes',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
        'score' => '?float',
        'passing_score' => '?float',
        'passed' => 'boolean',
        'completion_percentage' => '?int',
    ];

    protected $validationRules = [
        'employee_id'       => 'required|integer',
        'course_id'         => 'required|integer',
        'enrollment_date'   => 'permit_empty|valid_date',
        'completion_status' => 'permit_empty|in_list[Not Started,In Progress,Completed,Failed,Withdrawn]',
        'completion_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        'score'             => 'permit_empty|numeric',
        'passing_score'     => 'permit_empty|numeric',
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
        if (!empty($d['scheduled_start_date']) && !empty($d['scheduled_end_date'])) {
            if (strtotime($d['scheduled_end_date']) < strtotime($d['scheduled_start_date'])) {
                throw new \InvalidArgumentException('scheduled_end_date must be after scheduled_start_date');
            }
        }
        if (!empty($d['actual_start_date']) && !empty($d['actual_end_date'])) {
            if (strtotime($d['actual_end_date']) < strtotime($d['actual_start_date'])) {
                throw new \InvalidArgumentException('actual_end_date must be after actual_start_date');
            }
        }
        return $data;
    }

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}
