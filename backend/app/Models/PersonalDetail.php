<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\EncryptableModel;

class PersonalDetail extends Model
{
    use EncryptableModel;
    
    protected $table = 'personal_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'gender',
        'marital_status',
        'blood_group',
        'religion',
        'passport_number_encrypted',
        'passport_expiry',
        'visa_status',
        'work_authorization_number_encrypted',
        'work_authorization_expiry',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $validationRules = [
        'employee_id' => 'required|is_not_unique[personal_details.employee_id]',
        'gender' => 'permit_empty|in_list[Male,Female,Other]',
        'marital_status' => 'permit_empty|in_list[Single,Married,Divorced,Widowed]'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
