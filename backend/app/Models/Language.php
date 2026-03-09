<?php

namespace App\Models;

use CodeIgniter\Model;

class Language extends Model
{
    protected $table = 'languages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'language',
        'proficiency',
        'can_read',
        'can_write',
        'can_speak',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
        'can_read' => 'boolean',
        'can_write' => 'boolean',
        'can_speak' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id' => 'required|integer',
        'language'    => 'required|max_length[100]',
        'proficiency' => 'permit_empty|in_list[Native,Fluent,Intermediate,Basic]',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
