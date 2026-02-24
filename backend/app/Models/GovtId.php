<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\EncryptableModel;

class GovtId extends Model
{
    use EncryptableModel;
    
    protected $table = 'govt_ids';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'id_type',
        'id_number_encrypted',
        'id_number_hash',
        'issue_date',
        'expiry_date',
        'issuing_country',
        'issuing_authority',
        'document_url',
        'is_primary',
        'verified',
        'verification_date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verification_date' => 'date'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
