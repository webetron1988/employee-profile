<?php

namespace App\Models;

use CodeIgniter\Model;

class OrgHierarchy extends Model
{
    protected $table = 'org_hierarchy';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'parent_id',
        'department',
        'division',
        'section',
        'team',
        'org_level',
        'hierarchy_path',
        'is_manager',
        'team_size',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_manager' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(OrgHierarchy::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(OrgHierarchy::class, 'parent_id', 'id');
    }
}
