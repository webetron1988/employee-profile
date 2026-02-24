<?php

namespace App\Models;

use CodeIgniter\Model;

class Skill extends Model
{
    protected $table = 'skills';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'skill_name',
        'skill_category',
        'skill_level',
        'description',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $validationRules = [
        'skill_name' => 'required|is_unique[skills.skill_name]',
        'skill_category' => 'required|string'
    ];

    // Relationships
    public function employees()
    {
        return $this->hasMany(EmployeeSkill::class, 'skill_id', 'id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'skill_id', 'id');
    }
}
