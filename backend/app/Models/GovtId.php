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
    protected $useSoftDeletes = true;
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
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
        'is_primary' => 'boolean',
        'verified' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'        => 'required|integer',
        'id_type'            => 'required|max_length[100]',
        'issue_date'         => 'permit_empty|valid_date',
        'expiry_date'        => 'permit_empty|valid_date',
        'issuing_country'    => 'permit_empty|max_length[100]',
        'issuing_authority'  => 'permit_empty|max_length[255]',
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
