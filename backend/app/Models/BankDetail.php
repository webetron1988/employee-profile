<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\EncryptableModel;

class BankDetail extends Model
{
    use EncryptableModel;
    
    protected $table = 'bank_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'bank_name',
        'account_number_encrypted',
        'account_number_hash',
        'account_type',
        'ifsc_code',
        'branch_name',
        'account_holder_name',
        'is_primary',
        'verified',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
