<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceDocument extends Model
{
    protected $table = 'compliance_documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'document_type',
        'document_name',
        'document_url',
        'issue_date',
        'expiry_date',
        'status',
        'signed_date',
        'signed_by_id',
        'comments',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'signed_date' => 'date'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by_id', 'id');
    }
}
