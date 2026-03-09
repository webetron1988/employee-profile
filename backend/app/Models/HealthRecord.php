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
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'blood_group',
        'height',
        'weight',
        'is_blood_donor',
        'vision_status',
        'correction_type',
        'color_blindness',
        'physical_disability',
        'identification_marks',
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
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
    ];

    protected $validationRules = [
        'employee_id'              => 'required|integer',
        'blood_group'              => 'permit_empty|in_list[A+,A-,B+,B-,AB+,AB-,O+,O-]',
        'emergency_contact_phone'  => 'permit_empty|regex_match[/^\+?[0-9\s\-\(\)]{7,20}$/]',
        'last_medical_checkup_date' => 'permit_empty|valid_date',
        'health_insurance_provider' => 'permit_empty|max_length[255]',
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
