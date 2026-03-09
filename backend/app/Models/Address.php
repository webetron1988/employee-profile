<?php

namespace App\Models;

use CodeIgniter\Model;

class Address extends Model
{
    protected $table = 'addresses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'address_type',
        'street_address',
        'city',
        'state',
        'postal_code',
        'country',
        'is_primary',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected array $casts = [
        'is_primary' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'    => 'required|integer',
        'address_type'   => 'permit_empty|in_list[Residential,Permanent,Official,Other]',
        'street_address' => 'required|max_length[500]',
        'city'           => 'required|max_length[100]',
        'postal_code'    => 'permit_empty|max_length[20]',
        'country'        => 'permit_empty|max_length[100]',
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
