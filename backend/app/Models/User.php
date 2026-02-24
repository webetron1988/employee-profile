<?php

namespace App\Models;

use CodeIgniter\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'email',
        'password_hash',
        'employee_id',
        'role',
        'permissions',
        'is_active',
        'last_login_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'permissions' => 'json',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_login_at' => 'datetime'
    ];

    protected $validationRules = [
        'email' => 'required|valid_email|is_unique[users.email]',
        'role' => 'required|in_list[admin,hr,manager,employee,system]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];

    // Relationship
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    // Check if user has permission
    public function hasPermission($action)
    {
        $permissions = $this->permissions ?? [];
        return in_array($action, $permissions['actions'] ?? []);
    }
}
