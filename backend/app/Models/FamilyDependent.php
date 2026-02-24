<?php

namespace App\Models;

use CodeIgniter\Model;

class FamilyDependent extends Model
{
    protected $table = 'family_dependents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'name',
        'relationship',
        'date_of_birth',
        'occupation',
        'education_level',
        'dependent_for_insurance',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'dependent_for_insurance' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date_of_birth' => 'date'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
