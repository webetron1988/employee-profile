<?php

namespace App\Models;

use CodeIgniter\Model;

class Promotion extends Model
{
    protected $table = 'promotions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'previous_designation',
        'new_designation',
        'previous_grade',
        'new_grade',
        'promotion_date',
        'effective_date',
        'promotion_reason',
        'salary_increment_percentage',
        'approval_status',
        'approved_by_id',
        'approved_date',
        'comments',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'salary_increment_percentage' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'promotion_date' => 'date',
        'effective_date' => 'date',
        'approved_date' => 'date'
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
