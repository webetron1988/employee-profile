<?php

namespace App\Models;

use CodeIgniter\Model;

class EmergencyContact extends Model
{
    protected $table = 'emergency_contacts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'contact_name',
        'relationship',
        'phone_number',
        'email',
        'address',
        'is_primary',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
