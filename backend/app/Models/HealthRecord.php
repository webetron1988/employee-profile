<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\EncryptableModel;

class HealthRecord extends Model
{
    use EncryptableModel;
    
    protected $table = 'health_records';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'blood_group',
        'allergies',
        'chronic_conditions',
        'medications',
        'health_insurance_provider',
        'health_insurance_number_encrypted',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'last_medical_checkup_date',
        'medical_notes',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_medical_checkup_date' => 'date'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
