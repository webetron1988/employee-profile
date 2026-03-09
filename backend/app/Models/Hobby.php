<?php

namespace App\Models;

use CodeIgniter\Model;

class Hobby extends Model
{
    protected $table = 'hobbies';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_id',
        'category',
        'name',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $validationRules = [
        'employee_id' => 'required|integer',
        'category'    => 'required|in_list[hobby,sport,talent]',
        'name'        => 'required|max_length[255]',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
