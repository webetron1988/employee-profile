<?php

namespace App\Models;

use CodeIgniter\Model;

class TrainingHistory extends Model
{
    protected $table = 'training_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'training_name',
        'training_type',
        'training_provider',
        'training_date',
        'duration_hours',
        'location',
        'mode',
        'cost',
        'trainer_name',
        'assessment_score',
        'certificate_obtained',
        'certificate_url',
        'feedback',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'cost' => 'float',
        'duration_hours' => 'int',
        'assessment_score' => 'float',
        'certificate_obtained' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'training_date' => 'date'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
