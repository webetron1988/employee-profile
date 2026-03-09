<?php

namespace App\Models;

use CodeIgniter\Model;

class Employee extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'hrms_employee_id',
        'email',
        'first_name',
        'last_name',
        'date_of_birth',
        'nationality',
        'phone',
        'profile_picture_url',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $validationRules = [
        'email' => 'required|valid_email|is_unique[employees.email]',
        'first_name' => 'required|string|max_length[100]',
        'last_name' => 'required|string|max_length[100]',
        'hrms_employee_id' => 'required|string|is_unique[employees.hrms_employee_id]',
        'phone' => 'permit_empty|regex_match[/^\+?[0-9\s\-\(\)]{7,20}$/]',
    ];

    // Relationships
    public function personalDetail()
    {
        return $this->hasOne(PersonalDetail::class, 'employee_id', 'id');
    }

    public function jobInformation()
    {
        return $this->hasOne(JobInformation::class, 'employee_id', 'id');
    }

    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class, 'employee_id', 'id');
    }

    public function skills()
    {
        return $this->hasMany(EmployeeSkill::class, 'employee_id', 'id');
    }

    public function competencies()
    {
        return $this->hasMany(EmployeeCompetency::class, 'employee_id', 'id');
    }

    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class, 'employee_id', 'id');
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class, 'employee_id', 'id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'employee_id', 'id');
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class, 'employee_id', 'id');
    }

    public function getFullName()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
