<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeSkill extends Model
{
    protected $table = 'employee_skills';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'skill_id',
        'proficiency_level',
        'years_of_experience',
        'last_used_date',
        'endorsements',
        'verified',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'years_of_experience' => 'float',
        'verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_used_date' => 'date'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'id');
    }
}
