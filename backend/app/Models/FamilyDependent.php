<?php

namespace App\Models;

use CodeIgniter\Model;

class FamilyDependent extends Model
{
    protected $table = 'family_dependents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'name',
        'relationship',
        'date_of_birth',
        'gender',
        'contact_number',
        'occupation',
        'education_level',
        'dependent_for_insurance',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
        'dependent_for_insurance' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'   => 'required|integer',
        'name'          => 'required|max_length[255]',
        'relationship'  => 'required|max_length[100]',
        'date_of_birth' => 'permit_empty|valid_date',
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
