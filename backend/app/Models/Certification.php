<?php

namespace App\Models;

use CodeIgniter\Model;

class Certification extends Model
{
    protected $table = 'certifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'certification_name',
        'certification_code',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'certificate_number',
        'certificate_url',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $validationRules = [
        'employee_id'        => 'required|integer',
        'certification_name' => 'required|max_length[255]',
        'issue_date'         => 'permit_empty|valid_date',
        'expiry_date'        => 'permit_empty|valid_date',
        'status'             => 'permit_empty|in_list[Active,Expired,Revoked,Pending]',
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
