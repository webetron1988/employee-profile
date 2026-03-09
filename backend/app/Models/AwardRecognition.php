<?php

namespace App\Models;

use CodeIgniter\Model;

class AwardRecognition extends Model
{
    protected $table = 'awards_recognition';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'award_name',
        'award_category',
        'award_date',
        'awarding_organization',
        'award_description',
        'monetary_reward',
        'certificate_url',
        'recognized_by_id',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
        'monetary_reward' => '?float',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function recognizedBy()
    {
        return $this->belongsTo(User::class, 'recognized_by_id', 'id');
    }
}
