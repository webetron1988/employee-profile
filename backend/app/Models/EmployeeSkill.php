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

    protected array $casts = [
        'years_of_experience' => '?float',
        'verified' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'        => 'required|integer',
        'skill_id'           => 'required|integer',
        'proficiency_level'  => 'permit_empty|in_list[Beginner,Intermediate,Advanced,Expert]',
        'years_of_experience' => 'permit_empty|numeric|greater_than_equal_to[0]',
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
