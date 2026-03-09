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
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'gender',
        'marital_status',
        'blood_group',
        'religion',
        'preferred_name',
        'name_arabic',
        'pronouns',
        'country_of_birth',
        'state_of_birth',
        'city_of_birth',
        'citizenship_type',
        'spouse_name',
        'marriage_date',
        'denomination',
        'passport_number_encrypted',
        'passport_expiry',
        'visa_status',
        'work_authorization_number_encrypted',
        'work_authorization_expiry',
        'linkedin_url',
        'github_url',
        'website_url',
        'twitter_url',
        'work_phone',
        'work_extension',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $validationRules = [
        'employee_id'    => 'required|integer',
        'gender'         => 'permit_empty|in_list[Male,Female,Other,Prefer Not to Say]',
        'marital_status' => 'permit_empty|in_list[Single,Married,Divorced,Widowed]',
        'blood_group'    => 'permit_empty|in_list[A+,A-,B+,B-,AB+,AB-,O+,O-]',
        'passport_expiry'           => 'permit_empty|valid_date',
        'marriage_date'             => 'permit_empty|valid_date',
        'work_authorization_expiry' => 'permit_empty|valid_date',
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
