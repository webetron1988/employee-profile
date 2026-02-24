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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'issue_date' => 'date',
        'expiry_date' => 'date'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
