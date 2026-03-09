<?php

namespace App\Models;

use CodeIgniter\Model;

class MentoringProgram extends Model
{
    protected $table = 'mentoring_programs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'program_name',
        'role',
        'partner_name',
        'status',
        'start_date',
        'end_date',
        'description',
        'goals',
        'frequency',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
