<?php

namespace App\Models;

use CodeIgniter\Model;

class VolunteerActivity extends Model
{
    protected $table = 'volunteer_activities';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'activity',
        'organization',
        'role',
        'hours',
        'period',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $validationRules = [
        'employee_id'  => 'required|integer',
        'activity'     => 'required|max_length[255]',
        'organization' => 'permit_empty|max_length[255]',
        'hours'        => 'permit_empty|numeric',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
