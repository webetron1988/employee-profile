<?php

namespace App\Models;

use CodeIgniter\Model;

class MobilityPreference extends Model
{
    protected $table = 'mobility_preferences';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'open_to_mobility',
        'preferred_function',
        'preferred_location',
        'preferred_role',
        'international_interest',
        'remote_preference',
        'available_from',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
        'open_to_mobility' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'        => 'required|integer',
        'preferred_function' => 'permit_empty|max_length[255]',
        'preferred_location' => 'permit_empty|max_length[255]',
        'preferred_role'     => 'permit_empty|max_length[255]',
        'international_interest' => 'permit_empty|in_list[Yes,No,Maybe]',
        'remote_preference'  => 'permit_empty|in_list[Remote,Hybrid,On-site,Flexible]',
        'available_from'     => 'permit_empty|valid_date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
