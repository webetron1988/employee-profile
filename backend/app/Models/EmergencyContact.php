<?php

namespace App\Models;

use CodeIgniter\Model;

class EmergencyContact extends Model
{
    protected $table = 'emergency_contacts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'contact_name',
        'relationship',
        'phone_number',
        'email',
        'address',
        'is_primary',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
        'is_primary' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'  => 'required|integer',
        'contact_name' => 'required|max_length[255]',
        'phone_number' => 'required|regex_match[/^\+?[0-9\s\-\(\)]{7,20}$/]',
        'email'        => 'permit_empty|valid_email',
        'relationship' => 'permit_empty|max_length[100]',
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
