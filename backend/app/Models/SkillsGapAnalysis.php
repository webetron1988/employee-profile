<?php

namespace App\Models;

use CodeIgniter\Model;

class SkillsGapAnalysis extends Model
{
    protected $table = 'skills_gap_analysis';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'target_role',
        'skill_name',
        'current_level',
        'target_level',
        'priority',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
