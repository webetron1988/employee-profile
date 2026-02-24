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

    protected $casts = [
        'score' => 'float',
        'passing_score' => 'float',
        'passed' => 'boolean',
        'completion_percentage' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'enrollment_date' => 'date',
        'scheduled_start_date' => 'date',
        'scheduled_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date'
    ];

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
