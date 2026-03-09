<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkingCondition extends Model
{
    protected $table = 'working_conditions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'accommodation_required',
        'accommodation_type',
        'special_equipment',
        'last_ergonomic_assessment',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
        'accommodation_required' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'             => 'required|integer',
        'accommodation_type'      => 'permit_empty|max_length[255]',
        'special_equipment'       => 'permit_empty|max_length[500]',
        'last_ergonomic_assessment' => 'permit_empty|valid_date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
