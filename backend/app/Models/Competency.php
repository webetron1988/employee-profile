<?php

namespace App\Models;

use CodeIgniter\Model;

class Competency extends Model
{
    protected $table = 'competencies';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'competency_name',
        'competency_category',
        'description',
        'proficiency_levels',
        'status',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
        'proficiency_levels' => 'json',
    ];

    protected $validationRules = [
        'competency_name' => 'required|is_unique[competencies.competency_name]',
        'competency_category' => 'required|string'
    ];

    // Relationships
    public function employees()
    {
        return $this->hasMany(EmployeeCompetency::class, 'competency_id', 'id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'competency_id', 'id');
    }
}
