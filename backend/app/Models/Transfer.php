<?php

namespace App\Models;

use CodeIgniter\Model;

class Transfer extends Model
{
    protected $table = 'transfers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'previous_department',
        'new_department',
        'previous_location',
        'new_location',
        'transfer_type',
        'transfer_date',
        'effective_date',
        'transfer_reason',
        'key_achievement',
        'skills_gained',
        'approval_status',
        'approved_by_id',
        'approved_date',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_id', 'id');
    }
}
