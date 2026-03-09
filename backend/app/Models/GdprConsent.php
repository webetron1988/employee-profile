<?php

namespace App\Models;

use CodeIgniter\Model;

class GdprConsent extends Model
{
    protected $table = 'gdpr_consents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'consent_type',
        'consent_given',
        'consent_date',
        'withdrawal_date',
        'ip_address',
        'user_agent',
        'consent_version',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'consent_given' => 'boolean',
    ];

    protected $validationRules = [
        'employee_id'   => 'required|integer',
        'consent_type'  => 'required|in_list[data_processing,data_sharing,marketing,analytics]',
        'consent_given' => 'required|in_list[0,1]',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
