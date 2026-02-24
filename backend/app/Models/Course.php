<?php

namespace App\Models;

use CodeIgniter\Model;

class Course extends Model
{
    protected $table = 'courses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'course_name',
        'course_code',
        'description',
        'provider',
        'course_type',
        'duration_hours',
        'cost',
        'skill_id',
        'competency_id',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'cost' => 'float',
        'duration_hours' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $validationRules = [
        'course_name' => 'required|string|max_length[150]',
        'course_code' => 'required|is_unique[courses.course_code]',
        'course_type' => 'required|in_list[Online,Classroom,Hybrid,Self-Paced]'
    ];

    // Relationships
    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'id');
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class, 'competency_id', 'id');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id', 'id');
    }
}
