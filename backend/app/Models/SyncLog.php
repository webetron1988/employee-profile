<?php

namespace App\Models;

use CodeIgniter\Model;

class SyncLog extends Model
{
    protected $table = 'sync_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'sync_type',
        'status',
        'records_processed',
        'records_failed',
        'error_details',
        'started_at',
        'completed_at'
    ];

    protected $validationRules = [
        'sync_type' => 'required|in_list[employee_master,job_info,org_hierarchy,manager_relationships]',
        'status' => 'required|in_list[pending,running,completed,failed]'
    ];
}
