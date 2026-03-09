<?php

namespace App\Models;

use CodeIgniter\Model;

class CareerPath extends Model
{
    protected $table = 'career_paths';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'employee_id',
        'position_title',
        'grade_level',
        'timeline',
        'is_current',
        'sort_order',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $validationRules = [
        'employee_id'    => 'required|integer',
        'position_title' => 'required|max_length[255]',
        'grade_level'    => 'permit_empty|max_length[20]',
        'timeline'       => 'permit_empty|max_length[50]',
        'is_current'     => 'permit_empty|in_list[0,1]',
        'sort_order'     => 'permit_empty|integer',
    ];
}
