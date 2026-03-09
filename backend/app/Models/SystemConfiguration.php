<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemConfiguration extends Model
{
    protected $table = 'system_configurations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'config_key',
        'config_value',
        'config_type',
        'description',
        'is_encrypted',
        'is_active',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
        'is_encrypted' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $validationRules = [
        'config_key' => 'required|is_unique[system_configurations.config_key]',
        'config_type' => 'required|in_list[Boolean,Integer,String,JSON,Array]'
    ];

    // Helper method to get config value
    public function getValue($key, $default = null)
    {
        $config = $this->where('config_key', $key)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return $default;
        }

        $value = $config['config_value'];

        // Parse based on type
        switch ($config['config_type']) {
            case 'Boolean':
                return (bool) $value;
            case 'Integer':
                return (int) $value;
            case 'JSON':
            case 'Array':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
