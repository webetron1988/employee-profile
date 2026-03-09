<?php

namespace App\Models;

use CodeIgniter\Model;

class PhysicalLocation extends Model
{
    protected $table = 'physical_locations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'office_name',
        'building',
        'floor',
        'desk',
        'work_arrangement',
        'office_days',
        'time_zone',
        'country',
        'region',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useSoftDeletes = true;

    protected $validationRules = [
        'employee_id'      => 'required',
        'office_name'      => 'permit_empty|string|max_length[150]',
        'building'         => 'permit_empty|string|max_length[100]',
        'floor'            => 'permit_empty|string|max_length[50]',
        'desk'             => 'permit_empty|string|max_length[50]',
        'work_arrangement' => 'permit_empty|in_list[On-site,Hybrid,Remote]',
        'office_days'      => 'permit_empty|integer|less_than_equal_to[7]',
        'time_zone'        => 'permit_empty|string|max_length[100]',
        'country'          => 'permit_empty|string|max_length[100]',
        'region'           => 'permit_empty|string|max_length[100]',
    ];
}
