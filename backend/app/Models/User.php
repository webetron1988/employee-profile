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
        'hrms_employee_id',
        'first_name',
        'last_name',
        'phone',
        'profile_picture_url',
        'role',
        'permissions',
        'is_active',
        'last_login_at',
        'refresh_token_hash',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
        'is_active' => 'boolean',
    ];

    protected $validationRules = [
        'email' => 'required|valid_email|is_unique[users.email]',
        'role' => 'required|in_list[admin,hr,manager,employee,system]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];

    // Check if user has permission
    public function hasPermission($action)
    {
        $permissions = $this->permissions ?? [];
        return in_array($action, $permissions['actions'] ?? []);
    }

    public function getFullName()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
