<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'employee_id',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'old_value',
        'new_value',
        'change_reason',
        'ip_address',
        'user_agent',
        'status',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    protected $allowedReturnTypes = ['array', 'object'];

    // Relationship
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
