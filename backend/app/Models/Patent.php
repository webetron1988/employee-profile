<?php

namespace App\Models;

use CodeIgniter\Model;

class Patent extends Model
{
    protected $table = 'patents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'title',
        'description',
        'filing_date',
        'status',
        'patent_number',
        'reward_amount',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $validationRules = [
        'employee_id'  => 'required|integer',
        'title'        => 'required|max_length[255]',
        'filing_date'  => 'permit_empty|valid_date',
        'status'       => 'permit_empty|in_list[Filed,Pending,Granted,Rejected]',
        'reward_amount' => 'permit_empty|numeric',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
